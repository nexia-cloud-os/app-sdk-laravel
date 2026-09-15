<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional owner-side bulk resolution for import and other batch workloads. */
interface ResourceReferenceBatchResolutionContribution extends ResourceReferenceResolutionContribution
{
    /**
     * @param  list<string>  $resourceIds
     * @return array<string, ResolvedResourceReference> References keyed by resource id.
     */
    public function resolveMany(
        array $resourceIds,
        ResourceReferenceResolutionContext $context,
    ): array;
}
