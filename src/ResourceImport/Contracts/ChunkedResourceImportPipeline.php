<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\ResourceImport\ImportBatchState;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/** Optional V2 capability for pipelines that validate repeatable row sources. */
interface ChunkedResourceImportPipeline
{
    /**
     * @param  array<string, mixed>  $mapping
     */
    public function validateRowSource(
        string $batchId,
        LegalEntity $legalEntity,
        Actor $actor,
        ResourceImportRowSource $rows,
        array $mapping,
    ): ImportBatchState;
}
