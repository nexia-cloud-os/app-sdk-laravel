<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface PermissionAuthorizer
{
    public function allowsPermission(
        Authenticatable $user,
        string $permissionKey,
        ?int $legalEntityId = null,
        ?int $operatingUnitId = null,
        bool $resourcePolicyRequired = true,
    ): bool;

    /** @return list<int> */
    public function legalEntityKeysForPermission(
        Authenticatable $user,
        string $permissionKey,
        bool $resourcePolicyRequired = true,
    ): array;
}
