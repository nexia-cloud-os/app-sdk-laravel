<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureRequestPreparationReference;
use Nexia\Signature\SignatureRequestPreparationSubmission;
use Nexia\Signature\SignatureRequestResult;
use Nexia\Signature\SignatureRequestSubmission;

/**
 * Starts a request-local document-editing preparation from an App-authorized
 * canonical snapshot. The host owns the editable document draft; the App
 * retains ownership of every business value that produced this snapshot.
 */
interface SignatureRequestPreparationHost
{
    public function begin(
        SignatureRequestPreparationSubmission $submission,
    ): SignatureRequestPreparationReference;

    /**
     * Creates or returns a signature request from the exact ready document
     * projected by an App-started preparation. The host preserves ordinary
     * SignatureRequestSubmission idempotency semantics and records the
     * preparation provenance in the same transaction as request acceptance.
     */
    public function submit(
        SignatureRequestPreparationReference $preparation,
        SignatureRequestSubmission $submission,
    ): SignatureRequestResult;
}
