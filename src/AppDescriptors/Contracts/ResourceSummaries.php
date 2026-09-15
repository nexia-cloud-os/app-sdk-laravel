<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors\Contracts;

use Nexia\AppDescriptors\ResourceSummary;
use Nexia\AppDescriptors\ResourceSummaryContext;
use Nexia\AppDescriptors\Contracts\ResourceSummaryContribution;

/** Host discovery surface for App-contributed Resource Summary providers. */
interface ResourceSummaries
{
    public function resolve(string $resourceKey): ?ResourceSummaryContribution;

    public function summarize(
        string $resourceKey,
        string $resourceId,
        ResourceSummaryContext $context,
    ): ?ResourceSummary;
}
