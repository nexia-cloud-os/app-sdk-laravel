<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional owner-side resolution across dates and filters in one bounded request. */
interface ResourceReferenceGroupedResolutionContribution extends ResourceReferenceBatchResolutionContribution
{
    /**
     * Apply each group's dates and filters to the supplied actor, organization,
     * purpose and sort. Enforce authorization for every group; omit missing or
     * denied references. Do not reuse authorization across actors or requests.
     * Results must retain group keys and each group's exact as-of time.
     *
     * @param array<string, array{resource_ids:list<string>, as_of:DateTimeImmutable, filters?:array<string, string|list<string>>, effective_through?:DateTimeImmutable}> $groups
     * @return array<string, array<string, ResolvedResourceReference>>
     */
    public function resolveManyGrouped(array $groups, ResourceReferenceResolutionContext $context): array;
}
