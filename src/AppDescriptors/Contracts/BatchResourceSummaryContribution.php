<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors\Contracts;

use Nexia\AppDescriptors\ResourceSummary;
use Nexia\AppDescriptors\ResourceSummaryContext;

/** Optional bulk resolver for surfaces that present several records at once. */
interface BatchResourceSummaryContribution extends ResourceSummaryContribution
{
    /**
     * @param  list<string>  $resourceIds
     * @return array<string, ResourceSummary> resource id => summary
     */
    public function summarizeMany(array $resourceIds, ResourceSummaryContext $context): array;
}
