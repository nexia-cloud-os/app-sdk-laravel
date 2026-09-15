<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors\Contracts;

use Nexia\AppDescriptors\ResourceSummary;
use Nexia\AppDescriptors\ResourceSummaryContext;

/** App-owned, authorization-safe summary for a resource referenced by Core or another App. */
interface ResourceSummaryContribution
{
    public function resourceKey(): string;

    public function summarize(string $resourceId, ResourceSummaryContext $context): ?ResourceSummary;
}
