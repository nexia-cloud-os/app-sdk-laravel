<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Whether a request lifecycle action was accepted or replayed by its idempotency identity. */
enum SignatureRequestActionStatus: string
{
    case Accepted = 'accepted';
    case Existing = 'existing';
}
