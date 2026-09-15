<?php

declare(strict_types=1);

namespace Nexia\Permission\Concerns;

use Nexia\Permission\AssignmentScope;
use Nexia\Permission\ResourcePermissionDefinition;

trait ContributesResourcePermissions
{
    public static function permissionCatalogGroup(): ?string
    {
        return static::resourcePermissionDefinition()->catalogGroup();
    }

    public static function permissionResource(): string
    {
        return static::resourcePermissionDefinition()->primaryResource();
    }

    public static function permissionAssignmentScope(): AssignmentScope
    {
        return AssignmentScope::LegalEntity;
    }

    /** @return array<string, list<string>> */
    public static function permissionResources(): array
    {
        return static::resourcePermissionDefinition()->resources();
    }

    /** @return list<array{key: string, app_key: string, resource: string, action: string, group: string, assignment_scope: string}> */
    public static function permissionDefinitions(string $appKey, ?string $group = null): array
    {
        return static::resourcePermissionDefinition()->definitions(
            $appKey,
            $group,
            static::permissionResources(),
            static::permissionAssignmentScope(),
        );
    }

    /** @return list<array{key: string, app_key: string, resource: string, action: string, group: string, assignment_scope: string}> */
    public static function catalogPermissionDefinitions(): array
    {
        return static::resourcePermissionDefinition()->catalogDefinitions();
    }

    private static function resourcePermissionDefinition(): ResourcePermissionDefinition
    {
        return ResourcePermissionDefinition::for(static::class);
    }
}
