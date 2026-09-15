<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional owner-authorized candidate search for selectors and self-context discovery. */
interface ResourceReferenceSearchContribution extends ResourceReferenceResolutionContribution
{
    /** @return list<ResolvedResourceReference> */
    public function search(
        string $query,
        ResourceReferenceResolutionContext $context,
        int $limit,
    ): array;
}
