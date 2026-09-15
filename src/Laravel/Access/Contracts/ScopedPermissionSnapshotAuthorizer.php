<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Resolves read-only Tenant and Legal Entity permission affordances for
 * explicit Legal Entity contexts.
 *
 * Protected writes continue to use point-in-time authorization decisions.
 */
interface ScopedPermissionSnapshotAuthorizer
{
    /**
     * Resolve every requested permission key for each Legal Entity public ID.
     *
     * The returned map contains every requested string key exactly as provided;
     * non-string entries are ignored. Resolution fails closed for unknown,
     * retired, unavailable, delegated-away, or out-of-scope permissions.
     *
     * @param  list<string>  $legalEntityPublicIds
     * @param  list<string>  $permissionKeys
     * @return array<string, array<string, bool>> one false-by-default map per requested string ID
     */
    public function forLegalEntityPermissions(
        Authenticatable $user,
        array $legalEntityPublicIds,
        array $permissionKeys,
    ): array;
}
