<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Nexia\Organization\OrganizationTarget;
use Nexia\Organization\OrganizationTargetQuery;
use Nexia\Organization\OrganizationTargetSet;

/** Resolves exact host-authorized organization targets for one host-owned permission. */
interface OrganizationTargetResolver
{
    public function resolveListTargets(
        Authenticatable $user,
        string $permissionKey,
        OrganizationTargetQuery $query,
    ): OrganizationTargetSet;

    public function resolveCreateTarget(
        Authenticatable $user,
        string $permissionKey,
        OrganizationTargetQuery $query,
    ): OrganizationTarget;
}
