<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional exact search whose non-null result is complete for the requested terms. */
interface CompleteEffectiveRangeReferences
{
    /** @param list<string> $queries @return list<ResolvedResourceReference>|null */
    public function searchCompleteEffectiveRange(string $resourceKey, array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context): ?array;
}
