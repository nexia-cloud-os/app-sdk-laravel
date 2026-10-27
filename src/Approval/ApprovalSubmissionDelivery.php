<?php

declare(strict_types=1);

namespace Nexia\Approval;

use InvalidArgumentException;

/** A scoped instruction for the App's declared, idempotent finalization work handler. */
final readonly class ApprovalSubmissionDelivery
{
    public function __construct(
        public string $submissionId,
        public string $outcome,
        public ?string $casePublicId = null,
    ) {
        if (! in_array($outcome, ['commit', 'release'], true)
            || ($outcome === 'commit') !== ($casePublicId !== null)) {
            throw new InvalidArgumentException('Invalid approval submission delivery.');
        }
    }
}
