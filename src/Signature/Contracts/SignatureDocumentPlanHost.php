<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\CurrentBoundSignableDocumentResult;
use Nexia\Signature\PreparedSignableDocumentResult;
use Nexia\Signature\SignatureDocumentPlanSubmission;

/** Executes a composable source/participant/trusted-asset document plan. */
interface SignatureDocumentPlanHost
{
    public function submit(
        SignatureDocumentPlanSubmission $submission,
    ): CurrentBoundSignableDocumentResult|PreparedSignableDocumentResult;
}
