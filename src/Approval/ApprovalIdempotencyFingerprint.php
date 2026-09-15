<?php

declare(strict_types=1);

namespace Nexia\Approval;

use Nexia\Support\CanonicalPayloadFingerprint;

/** Canonical request fingerprinting shared by Approval idempotency callers. */
final class ApprovalIdempotencyFingerprint
{
    /** @param array<string, mixed> $payload */
    public static function forPayload(array $payload): string
    {
        return CanonicalPayloadFingerprint::sha256($payload);
    }
}
