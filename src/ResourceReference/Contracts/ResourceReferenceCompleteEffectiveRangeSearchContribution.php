<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Return null if the complete authorized result cannot be returned within the bound. */
interface ResourceReferenceCompleteEffectiveRangeSearchContribution extends ResourceReferenceResolutionContribution
{
    /** @param list<string> $queries @return list<ResolvedResourceReference>|null */
    public function searchCompleteEffectiveRange(array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $limit): ?array;
}
