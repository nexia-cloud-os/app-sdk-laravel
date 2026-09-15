<?php

declare(strict_types=1);

namespace Nexia\Permission;

final class ResourcePermissionDefinition
{
    /** @param class-string $contributorClass */
    private function __construct(private readonly string $contributorClass) {}

    /** @param class-string $contributorClass */
    public static function for(string $contributorClass): self
    {
        return new self($contributorClass);
    }

    public function catalogAppKey(): string
    {
        return $this->splitResourceKey()[0];
    }

    public function catalogGroup(): ?string
    {
        return null;
    }

    public function primaryResource(): string
    {
        $resources = $this->contributorClass::permissionResources();
        $first = array_key_first($resources);

        return ! is_string($first) || $first === ''
            ? $this->defaultResource()
            : $this->splitTarget($first, $this->catalogAppKey())[1];
    }

    /** @return array<string, list<string>> */
    public function resources(): array
    {
        return [$this->contributorClass::resourceKey() => ['read', 'manage']];
    }

    /**
     * @param  array<string, list<string>>|null  $resources
     * @return list<PermissionDefinition>
     */
    public function definitions(
        string $appKey,
        ?string $group = null,
        ?array $resources = null,
        AssignmentScope $assignmentScope = AssignmentScope::LegalEntity,
    ): array {
        $definitions = [];

        foreach (($resources ?? $this->contributorClass::permissionResources()) as $resource => $actions) {
            if (! is_string($resource) || $resource === '') {
                continue;
            }

            [$resolvedAppKey, $resolvedResource] = $this->splitTarget($resource, $appKey);
            $definitions = [
                ...$definitions,
                ...PermissionDefinition::many(
                    appKey: $resolvedAppKey,
                    resource: $resolvedResource,
                    actions: $actions,
                    group: $group ?? $resolvedAppKey,
                    assignmentScope: $assignmentScope,
                ),
            ];
        }

        return $definitions;
    }

    /** @return list<PermissionDefinition> */
    public function catalogDefinitions(): array
    {
        return $this->definitions(
            $this->catalogAppKey(),
            $this->contributorClass::permissionCatalogGroup(),
            $this->contributorClass::permissionResources(),
            $this->contributorClass::permissionAssignmentScope(),
        );
    }

    private function defaultResource(): string
    {
        return $this->splitResourceKey()[1];
    }

    /** @return array{0: string, 1: string} */
    private function splitResourceKey(): array
    {
        $resourceKey = $this->contributorClass::resourceKey();

        return str_contains($resourceKey, '.')
            ? explode('.', $resourceKey, 2)
            : [$resourceKey, $resourceKey];
    }

    /** @return array{0: string, 1: string} */
    private function splitTarget(string $target, ?string $fallbackAppKey): array
    {
        if (str_contains($target, '.')) {
            return explode('.', $target, 2);
        }

        return [$fallbackAppKey ?? $this->catalogAppKey(), $target];
    }
}
