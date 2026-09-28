<?php

declare(strict_types=1);

namespace Nexia\Actions;

use Nexia\AppDescriptors\ResourceActionEffect;

use InvalidArgumentException;

/**
 * One App-owned business operation, reused across its permitted placements.
 *
 * It describes the operation already implemented by the owning App. Hosts use
 * this to discover and invoke a Resource action without minting a separate
 * integration contract for every action.
 */
final readonly class ActionDefinition
{
    /** Derive the existing Agent transport from the same business action declaration. */
    public function agentTool(string $qualifiedKey): \Nexia\Agent\AgentToolDeclaration
    {
        if (! in_array(ActionPlacement::Agent, $this->placements, true)) {
            throw new InvalidArgumentException('This action is not available to Agents.');
        }
        $read = $this->effect === ResourceActionEffect::Read;
        if ($read !== ($this->method === 'GET')) {
            throw new InvalidArgumentException('Agent action HTTP method must match its declared effect.');
        }
        $schema = $this->inputSchema ?? ['type' => 'object', 'properties' => []];
        if ($this->targets !== ActionTargets::None) {
            $schema['properties'][$this->targetParameter] = $this->targets === ActionTargets::One
                ? ['type' => 'string', 'minLength' => 1, 'maxLength' => 200]
                : ['type' => 'array', 'items' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200], 'minItems' => 1, 'maxItems' => 100, 'uniqueItems' => true];
            $schema['required'] = array_values(array_unique([...($schema['required'] ?? []), $this->targetParameter]));
        }

        return new \Nexia\Agent\AgentToolDeclaration(
            $qualifiedKey,
            $read ? \Nexia\Agent\AgentToolTier::Auto : \Nexia\Agent\AgentToolTier::Confirm,
            new \Nexia\Agent\AgentToolRouting([\Nexia\Agent\AgentToolLane::DirectData],
                $read ? \Nexia\Agent\AgentToolEffect::Read : \Nexia\Agent\AgentToolEffect::Mutate, \Nexia\Agent\AgentToolSurface::Data),
            $this->description ?? $this->labelKey ?? $qualifiedKey, $this->method, $this->path, [$this->permission], inputSchema: $schema,
        );
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'permission' => $this->permission, 'method' => $this->method, 'path' => $this->path,
            'input_schema' => $this->inputSchema, 'effect' => $this->effect->value, 'label_key' => $this->labelKey,
            'description' => $this->description, 'targets' => $this->targets->value,
            'placements' => array_map(static fn (ActionPlacement $placement): string => $placement->value, $this->placements),
            'target_parameter' => $this->targetParameter, 'confirmation_key' => $this->confirmationKey];
    }

    public static function fromArray(array $definition): self
    {
        return new self(key: $definition['key'], permission: $definition['permission'], method: $definition['method'],
            path: $definition['path'], inputSchema: $definition['input_schema'], effect: ResourceActionEffect::from($definition['effect']),
            labelKey: $definition['label_key'], description: $definition['description'], targets: ActionTargets::from($definition['targets']),
            placements: array_map(ActionPlacement::from(...), $definition['placements']), targetParameter: $definition['target_parameter'],
            confirmationKey: $definition['confirmation_key']);
    }

    /**
     * @param  array<string, mixed>|null  $inputSchema
     * @param  list<ActionPlacement> $placements
     */
    public function __construct(
        public string $key,
        public string $permission,
        public string $method,
        public string $path,
        public ?array $inputSchema = null,
        public ResourceActionEffect $effect = ResourceActionEffect::Mutate,
        public ?string $labelKey = null,
        public ?string $description = null,
        public ActionTargets $targets = ActionTargets::One,
        public array $placements = [ActionPlacement::Agent],
        public string $targetParameter = 'id',
        public ?string $confirmationKey = null,
    ) {
        if (! array_is_list($placements) || $placements === []) {
            throw new InvalidArgumentException('An action must declare its permitted placements.');
        }
        $seen = [];
        foreach ($placements as $placement) {
            if (! $placement instanceof ActionPlacement || isset($seen[$placement->value])) {
                throw new InvalidArgumentException('Action placements must be unique ActionPlacement values.');
            }
            $seen[$placement->value] = true;
        }
        if (array_filter($placements, static fn (ActionPlacement $placement): bool => $placement !== ActionPlacement::Agent) !== []) {
            if ($labelKey === null || trim($labelKey) === '' || trim($labelKey) !== $labelKey) {
                throw new InvalidArgumentException('A human action must declare its label catalog key.');
            }
            $hasTarget = str_contains($path, '{'.$targetParameter.'}');
            if (($targets === ActionTargets::One) !== $hasTarget) {
                throw new InvalidArgumentException('Action route target must match its declared cardinality.');
            }
        }
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $targetParameter) !== 1) {
            throw new InvalidArgumentException('Action target parameter must be a field identifier.');
        }
        if (preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)*$/', $key) !== 1) {
            throw new InvalidArgumentException('Resource action key must be a lowercase dotted identifier.');
        }
        if (preg_match('/^[a-z][a-z0-9-]*\.[a-z][a-z0-9._-]*$/', $permission) !== 1) {
            throw new InvalidArgumentException('Resource action permission must be a valid permission key.');
        }

        if ($method !== strtoupper($method)
            || ! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException('Resource action method is unsupported.');
        }
        if (! str_starts_with($path, '/api/') || str_contains($path, '?') || str_contains($path, '#')) {
            throw new InvalidArgumentException('Resource action path must be an absolute API route template.');
        }
        if ($inputSchema !== null && array_is_list($inputSchema)) {
            throw new InvalidArgumentException('Resource action input schema must be a JSON object.');
        }
        if ($effect === ResourceActionEffect::Read && $method === 'DELETE') {
            throw new InvalidArgumentException('A read Resource action cannot use DELETE.');
        }
        if ($description !== null && trim($description) === '') {
            throw new InvalidArgumentException('Resource action description cannot be empty.');
        }
    }
}
