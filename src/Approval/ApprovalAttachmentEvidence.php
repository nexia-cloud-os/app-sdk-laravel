<?php

declare(strict_types=1);

namespace Nexia\Approval;

use InvalidArgumentException;

/** Immutable Media evidence accepted by one Approval submission. */
final readonly class ApprovalAttachmentEvidence
{
    public function __construct(
        public string $mediaUuid,
        public string $checksum,
        public string $scanVerdict,
        public ?string $scannedAt = null,
    ) {
        foreach (['mediaUuid' => $mediaUuid, 'checksum' => $checksum, 'scanVerdict' => $scanVerdict] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Approval attachment {$field} must be non-blank and normalized.");
            }
        }

        if ($scannedAt !== null && ($scannedAt === '' || $scannedAt !== trim($scannedAt))) {
            throw new InvalidArgumentException('Approval attachment scannedAt must be null or a normalized non-blank string.');
        }
    }
}
