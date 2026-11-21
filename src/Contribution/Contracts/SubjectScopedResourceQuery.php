<?php

declare(strict_types=1);

namespace Nexia\Contribution\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Nexia\Organization\OrganizationTarget;
use Nexia\Permission\SubjectPopulation;

/** App-owned row predicate for the granted subject populations of one organization target. */
interface SubjectScopedResourceQuery extends ResourceAuthorizationContribution
{
    /**
     * Restrict the supplied Resource query to the union of these granted
     * populations within this exact target, including any custom visibility constraints.
     * Individual record policies are still enforced. Reject unsupported populations.
     *
     * @param list<SubjectPopulation> $populations
     */
    public static function scopeSubjects(Builder $query, Authenticatable $actor, OrganizationTarget $target, array $populations): Builder;
}
