<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceImport\ImportPipelineStep;

/** Optional bounded execution for queued imports. */
interface IncrementalResourceImportPipeline extends ChunkedResourceImportPipeline
{
    /**
     * Commit a bounded unit and its progress atomically before returning pending.
     * The App owns its cursor and cross-chunk validation (including duplicates).
     * Recheck actor/owner authority on every invocation. A pending result must
     * never be interpreted as a validated or apply-eligible batch.
     *
     * @param array<string, mixed> $mapping
     */
    public function validateNext(
        string $batchId,
        LegalEntity $legalEntity,
        Actor $actor,
        SeekableResourceImportRowSource $rows,
        array $mapping,
    ): ImportPipelineStep;

    /** Advance a durable apply cursor without repeating committed writes. */
    public function applyNext(string $batchId, LegalEntity $legalEntity, Actor $actor): ImportPipelineStep;
}
