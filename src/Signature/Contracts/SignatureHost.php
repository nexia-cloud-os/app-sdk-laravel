<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureRequestActionResult;
use Nexia\Signature\SignatureRequestCancelInput;
use Nexia\Signature\SignatureRequestQueryInput;
use Nexia\Signature\SignatureRequestReissueInput;
use Nexia\Signature\SignatureRequestResendInput;
use Nexia\Signature\SignatureRequestResult;
use Nexia\Signature\SignatureRequestSubmission;
use Nexia\Signature\SignatureRequestSummary;

/**
 * App-neutral signature-request capability. Core owns persistence, scope and
 * policy enforcement, participant contact protection, and execution runtime.
 */
interface SignatureHost
{
    public function submit(SignatureRequestSubmission $submission): SignatureRequestResult;

    public function summary(SignatureRequestQueryInput $query): ?SignatureRequestSummary;

    /**
     * Returns the protected request-detail UI URL only after Core has restored
     * the actor, Legal Entity, subject binding, and metadata authority.
     */
    public function requestDetailHrefIfAuthorized(SignatureRequestQueryInput $query): ?string;

    /**
     * Returns the protected completed-document UI URL only when the request is
     * complete and the actor may read its finalized artifact.
     */
    public function completedDocumentDetailHrefIfAuthorized(SignatureRequestQueryInput $query): ?string;

    public function cancel(SignatureRequestCancelInput $input): SignatureRequestActionResult;

    public function resend(SignatureRequestResendInput $input): SignatureRequestActionResult;

    public function reissue(SignatureRequestReissueInput $input): SignatureRequestResult;
}
