<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Nexia\Laravel\Access\PermissionAuthorizerResolver;

trait EvaluatesPermissionDecision
{
    protected function allowsPermission(
        Authenticatable $user,
        string $ability,
        ?int $legalEntityId = null,
        ?int $operatingUnitId = null,
    ): bool {
        return PermissionAuthorizerResolver::resolve()->allowsPermission(
            $user,
            $ability,
            legalEntityId: $legalEntityId,
            operatingUnitId: $operatingUnitId,
            resourcePolicyRequired: true,
        );
    }
}
