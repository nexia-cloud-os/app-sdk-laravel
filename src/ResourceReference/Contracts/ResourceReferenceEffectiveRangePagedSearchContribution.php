<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResourceReferencePage;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional owner-side pagination for complete effective-range populations. */
interface ResourceReferenceEffectiveRangePagedSearchContribution extends ResourceReferenceResolutionContribution
{
    public function searchEffectiveRangePage(
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        ResourceReferenceResolutionContext $context,
        int $page,
        int $perPage,
    ): ResourceReferencePage;
}
