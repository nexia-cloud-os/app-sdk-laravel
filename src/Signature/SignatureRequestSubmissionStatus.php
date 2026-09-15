<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Whether request submission created a request or returned its exact idempotent result. */
enum SignatureRequestSubmissionStatus: string
{
    case Accepted = 'accepted';
    case Existing = 'existing';
}
