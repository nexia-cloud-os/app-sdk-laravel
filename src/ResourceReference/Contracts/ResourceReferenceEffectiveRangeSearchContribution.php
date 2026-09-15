<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;
use DateTimeImmutable;

/** Optional owner-side exact search across an effective-date range for batch workloads. */
interface ResourceReferenceEffectiveRangeSearchContribution extends ResourceReferenceSearchContribution
{
    /**
     * Return every authorized reference whose effective interval overlaps the
     * inclusive lookup range and whose owner-defined exact key matches one of
     * the supplied queries.
     *
     * @param  list<string>  $queries
     * @return list<ResolvedResourceReference>
     */
    public function searchEffectiveRange(
        array $queries,
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        ResourceReferenceResolutionContext $context,
        int $limit,
    ): array;
}
