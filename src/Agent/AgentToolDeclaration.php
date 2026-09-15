<?php

declare(strict_types=1);

namespace Nexia\Agent;

use InvalidArgumentException;

/**
 * Immutable declaration of one App-owned Laravel route exposed to the Agent.
 *
 * Gateway-owned handlers, inline closures, Core Actions, recipes, and host
 * widgets are intentionally outside this SDK contract.
 */
final readonly class AgentToolDeclaration
{
    public const FACET_EXTERNAL_SHARE = 'external-share';

    public string $key;

    public AgentToolTier $tier;

    public AgentToolRouting $routing;

    public string $description;

    public string $method;

    public string $path;

    /** @var non-empty-list<string> */
    public array $permissions;

    /** @var list<string> */
    public array $exercises;

    /** @var list<string> */
    public array $facets;

    /** @var array<string, mixed>|null */
    public ?array $inputSchema;

    /** @var list<class-string> */
    public array $invalidatedModels;

    /**
     * @param  non-empty-list<string>  $permissions
     * @param  list<string>  $exercises
     * @param  list<string>  $facets
     * @param  array<string, mixed>|null  $inputSchema
     * @param  list<class-string>  $invalidatedModels
     */
    public function __construct(
        string $key,
        AgentToolTier $tier,
        AgentToolRouting $routing,
        string $description,
        string $method,
        string $path,
        array $permissions,
        array $exercises = [],
        array $facets = [],
        ?array $inputSchema = null,
        array $invalidatedModels = [],
    ) {
        $key = trim($key);
        if (! preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_]*)+$/', $key)) {
            throw new InvalidArgumentException(
                'Agent tool key must contain an App namespace followed by lowercase dotted identifiers.',
            );
        }

        $description = trim($description);
        if ($description === '') {
            throw new InvalidArgumentException("Agent tool {$key} must declare a description.");
        }

        $method = strtoupper(trim($method));
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException("Agent tool {$key} has an unsupported HTTP method.");
        }

        $path = trim($path);
        if (! str_starts_with($path, '/') || str_contains($path, '?') || str_contains($path, '#')) {
            throw new InvalidArgumentException(
                "Agent tool {$key} path must be an absolute route template without a query or fragment.",
            );
        }

        $permissions = self::stringList($permissions, "Agent tool {$key} permissions", false);
        $exercises = self::stringList($exercises, "Agent tool {$key} exercises");
        $facets = self::stringList($facets, "Agent tool {$key} facets");
        $invalidatedModels = self::stringList(
            $invalidatedModels,
            "Agent tool {$key} invalidated models",
        );
        if ($inputSchema !== null && array_is_list($inputSchema)) {
            throw new InvalidArgumentException(
                "Agent tool {$key} input schema must be a non-empty JSON object.",
            );
        }

        $externalShare = in_array(self::FACET_EXTERNAL_SHARE, $facets, true);
        if ($routing->effect === AgentToolEffect::External && ! $externalShare) {
            throw new InvalidArgumentException(
                "External Agent tool {$key} must declare the external-share facet.",
            );
        }
        if ($externalShare && $routing->effect !== AgentToolEffect::External) {
            throw new InvalidArgumentException(
                "Agent tool {$key} may declare external-share only with the external routing effect.",
            );
        }
        if ($externalShare && $tier !== AgentToolTier::Confirm) {
            throw new InvalidArgumentException(
                "External-share Agent tool {$key} must default to confirm.",
            );
        }

        $this->key = $key;
        $this->tier = $tier;
        $this->routing = $routing;
        $this->description = $description;
        $this->method = $method;
        $this->path = $path;
        $this->permissions = $permissions;
        $this->exercises = $exercises;
        $this->facets = $facets;
        $this->inputSchema = $inputSchema;
        $this->invalidatedModels = $invalidatedModels;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'tier' => $this->tier->value,
            'description' => $this->description,
            'executor' => AgentToolExecutor::Laravel->value,
            'routing' => $this->routing->toArray(),
            'http' => [
                'method' => $this->method,
                'path' => $this->path,
            ],
            'permissions' => $this->permissions,
            'exercises' => $this->exercises,
            'facets' => $this->facets,
            'input_schema' => $this->inputSchema,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return list<string>
     */
    private static function stringList(array $values, string $label, bool $emptyAllowed = true): array
    {
        if (! array_is_list($values)) {
            throw new InvalidArgumentException("{$label} must be a list.");
        }

        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("{$label} must contain non-empty trimmed strings.");
            }
        }

        if (! $emptyAllowed && $values === []) {
            throw new InvalidArgumentException("{$label} must not be empty.");
        }

        if (count(array_unique($values)) !== count($values)) {
            throw new InvalidArgumentException("{$label} must contain unique values.");
        }

        return array_values($values);
    }
}
