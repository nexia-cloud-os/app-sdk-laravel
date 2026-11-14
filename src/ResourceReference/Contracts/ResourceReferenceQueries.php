<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;
use Nexia\ResourceReference\ResourceReferenceQuery;
use Nexia\ResourceReference\ResourceReferenceQueryBatch;

/** Optional query capability; existing ResourceReferences hosts remain compatible. */
interface ResourceReferenceQueries extends ResourceReferences
{
    /**
     * Select an ID, IDs, text search or effective-date range. The returned
     * data is always a list; missing and unauthorized references are omitted.
     * A null legacy page becomes PAGE_UNAVAILABLE, without guessing its cause.
     * Known read failures are returned in error, with no upstream message/body.
     * Legacy transport overrides that have not opted in return
     * UNSUPPORTED_IMPLEMENTATION before dispatch. Programming errors may still
     * propagate; inspect status before using data.
     * Remote transports without protected-value authority return
     * PROTECTED_VALUES_UNAVAILABLE before sending the request.
     *
     * Configure the immutable selector; execute with get().
     */
    public function query(string $resourceKey, ResourceReferenceResolutionContext $context): ResourceReferenceQuery;

    /**
     * Independent queries in input order. Each data item has the same shape as
     * query(). The outer status is success, partial or error. If the batch itself
     * is invalid, data is empty and the outer error is INVALID_BATCH. A shared
     * transport failure may affect multiple items; inspect every item status.
     * Identical ID selections in matching contexts can share one read. Where supported, separate ID
     * reads can share a transport while retaining their individual contexts.
     * Owner-side grouped reads across dates require explicit effectiveThrough.
     *
     * @param list<ResourceReferenceQuery> $requests
     * @return ResourceReferenceQueryBatch Execute with get().
     */
    public function queryBatch(array $requests): ResourceReferenceQueryBatch;
}
