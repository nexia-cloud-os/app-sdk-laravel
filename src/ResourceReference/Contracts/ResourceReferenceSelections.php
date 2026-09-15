<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResourceReferenceSelectionContext;
use Nexia\ResourceReference\ResourceReferenceSelectionPage;
use Nexia\ResourceReference\ResourceReferenceSelectionResult;

/** Core-governed browser and write resolution for declared Resource fields. */
interface ResourceReferenceSelections
{
    /** Party write resolution requires a caller-owned database transaction. */
    public function resolve(
        string $resourceId,
        ResourceReferenceSelectionContext $context,
    ): ResourceReferenceSelectionResult;

    public function options(
        string $query,
        ResourceReferenceSelectionContext $context,
        int $page = 1,
        int $perPage = 25,
    ): ResourceReferenceSelectionPage;
}
