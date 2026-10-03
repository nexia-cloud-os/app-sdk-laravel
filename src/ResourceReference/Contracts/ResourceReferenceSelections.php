<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResourceReferenceSelectionContext;
use Nexia\ResourceReference\ResourceReferenceSelectionPage;
use Nexia\ResourceReference\ResourceReferenceSelectionResult;

/** Core-governed browser and write resolution for declared Resource fields. */
interface ResourceReferenceSelections
{
    /**
     * Write resolution requires a caller-owned database transaction. Composer retains Party
     * merge/row locks in that transaction. Isolated execution records a completion condition;
     * a later conflict may retain App data and require review, never a shared rollback.
     */
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
