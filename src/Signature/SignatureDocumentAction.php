<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Document preparation actions never imply participant invitation. */
enum SignatureDocumentAction: string
{
    case PrepareCurrentBound = 'prepare_current_bound';
    case Replace = 'replace';
    case RebuildPreReady = 'rebuild_pre_ready';
    case Cancel = 'cancel';
}
