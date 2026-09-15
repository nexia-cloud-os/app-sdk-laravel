<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Resolves read-only tenant-scope permission affordances from one host snapshot.
 *
 * Protected writes continue to use point-in-time authorization decisions.
 */
interface TenantPermissionSnapshotAuthorizer
{
    /**
     * Resolve every requested permission key against one tenant-scope snapshot.
     *
     * The returned map contains an entry for every requested string key,
     * spelled exactly as provided. Keys are never normalized in any way: no
     * trimming, case folding, alias expansion, or prefix rewriting. Non-string
     * entries in `$permissionKeys` are ignored and produce no entry.
     *
     * Resolution is fail-closed. A key maps to `false` whenever it cannot be
     * resolved to a granted affordance, including when it is unknown to the
     * host, carries the retired lifecycle
     * (`Nexia\Permission\PermissionDefinition::LIFECYCLE_RETIRED`), belongs to
     * an app that is not operational for the tenant, is not declared at
     * `Nexia\Permission\AssignmentScope::Tenant`, or is otherwise unresolvable.
     * Only a positively resolved grant maps to `true`.
     *
     * Scope parameters are deliberately absent. Resolution always uses the
     * ambient request-context tenant scope, so callers cannot ask about another
     * legal entity or operating unit through this contract. That is a
     * documented asymmetry against `PermissionAuthorizer` and
     * `SubjectPermissionAuthorizer`, which take explicit scope arguments.
     *
     * @param  list<string>  $permissionKeys
     * @return array<string, bool> one entry per requested string key, verbatim
     */
    public function forPermissions(Authenticatable $user, array $permissionKeys): array;
}
