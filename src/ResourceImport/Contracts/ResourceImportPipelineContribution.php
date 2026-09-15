<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

/**
 * App contribution for a file-import pipeline the host may drive.
 *
 * Apps already own complete import pipelines — receive, validate, apply, retry —
 * with their own state machine, encrypted mapping storage, idempotency key, and
 * domain rules. A host-side file-import feature has to feed those rather than
 * replace them: committing transaction rows through a generic create path would
 * bypass exactly the duplicate and tolerance rules the pipeline exists to
 * enforce.
 *
 * The contract lives here rather than in the host because an App may not add new
 * host-namespace references, so a host-side interface would be unimplementable
 * from a package. Discovery mirrors
 * `Nexia\Laravel\ResourceTransfer\Contracts\ResourceTransferExportSourceContribution`: the App
 * declares, the host finds.
 */
interface ResourceImportPipelineContribution
{
    /** @return list<ResourceImportPipelineDefinition> */
    public static function resourceImportPipelines(): array;
}
