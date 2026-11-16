<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceImport\ImportPipelineStep;

/** Optional apply checkpoints bound to one host-issued work identity. */
interface WorkCheckpointedResourceImportPipeline extends IncrementalResourceImportPipeline
{
    /**
     * Atomically record a committed step under $workId with its business changes.
     * The same identity and batch must return the recorded step without applying
     * another chunk. Reject mismatched ownership and leave uncertain writes blocked.
     * The identity is supplied by the trusted work runtime, not by a user.
     */
    public function applyNextForWork(string $batchId, LegalEntity $legalEntity, Actor $actor, string $workId): ImportPipelineStep;
}
