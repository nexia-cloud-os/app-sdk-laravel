<?php

declare(strict_types=1);

namespace Nexia\Permission;

use ArrayAccess;
use IteratorAggregate;
use LogicException;
use Traversable;

final readonly class PermissionDefinition implements ArrayAccess, IteratorAggregate
{
    public const AUDIENCE_INTERNAL = 'internal';
    public const AUDIENCE_EXTERNAL = 'external';
    public const AUDIENCE_ANY = 'any';
    public const RISK_STANDARD = 'standard';
    public const RISK_ELEVATED = 'elevated';
    public const RISK_PRIVILEGED = 'privileged';
    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_RETIRED = 'retired';

    public function __construct(
        public string $key,
        public string $appKey,
        public string $resource,
        public string $action,
        public string $group,
        public AssignmentScope $assignmentScope,
        public string $label,
        public string $description,
        public string $audience,
        public string $risk,
        public GrantControl $grantControl,
        public string $lifecycle,
    ) {}

    public static function make(
        string $appKey,
        string $resource,
        string $action,
        ?string $group = null,
        ?string $key = null,
        AssignmentScope $assignmentScope = AssignmentScope::LegalEntity,
        ?string $label = null,
        ?string $description = null,
        string $audience = self::AUDIENCE_ANY,
        string $risk = self::RISK_STANDARD,
        string $lifecycle = self::LIFECYCLE_ACTIVE,
        GrantControl $grantControl = GrantControl::Standard,
    ): self {
        $resourceLabel = self::headline($resource);
        $actionLabel = self::headline($action);

        return new self(
            key: $key ?? "{$appKey}.{$resource}.{$action}",
            appKey: $appKey,
            resource: $resource,
            action: $action,
            group: $group ?? $appKey,
            assignmentScope: $assignmentScope,
            label: $label ?? "{$actionLabel} {$resourceLabel}",
            description: $description ?? "Allows {$actionLabel} on {$resourceLabel}.",
            audience: $audience,
            risk: $risk,
            grantControl: $grantControl,
            lifecycle: $lifecycle,
        );
    }

    /**
     * @param  list<string>  $actions
     * @return list<self>
     */
    public static function many(
        string $appKey,
        string $resource,
        array $actions,
        ?string $group = null,
        AssignmentScope $assignmentScope = AssignmentScope::LegalEntity,
        string $audience = self::AUDIENCE_ANY,
        string $risk = self::RISK_STANDARD,
        string $lifecycle = self::LIFECYCLE_ACTIVE,
        GrantControl $grantControl = GrantControl::Standard,
    ): array {
        return array_map(
            fn (string $action): self => self::make(
                $appKey,
                $resource,
                $action,
                $group,
                assignmentScope: $assignmentScope,
                audience: $audience,
                risk: $risk,
                lifecycle: $lifecycle,
                grantControl: $grantControl,
            ),
            $actions,
        );
    }

    public static function app(
        string $appKey,
        ?string $group = null,
        AssignmentScope $assignmentScope = AssignmentScope::LegalEntity,
    ): PermissionAppDefinition {
        return new PermissionAppDefinition($appKey, $group, $assignmentScope);
    }

    /** @return array{key: string, app_key: string, resource: string, action: string, group: string, assignment_scope: string, label: string, description: string, audience: string, risk: string, grant_control: string, lifecycle: string} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'app_key' => $this->appKey,
            'resource' => $this->resource,
            'action' => $this->action,
            'group' => $this->group,
            'assignment_scope' => $this->assignmentScope->value,
            'label' => $this->label,
            'description' => $this->description,
            'audience' => $this->audience,
            'risk' => $this->risk,
            'grant_control' => $this->grantControl->value,
            'lifecycle' => $this->lifecycle,
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->toArray()[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Permission definitions are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Permission definitions are immutable.');
    }

    public function getIterator(): Traversable
    {
        yield from $this->toArray();
    }

    private static function headline(string $value): string
    {
        $words = preg_replace('/(?<!^)[A-Z]/', ' $0', str_replace(['-', '_'], ' ', $value));

        return ucwords(strtolower(trim($words ?? $value)));
    }
}
