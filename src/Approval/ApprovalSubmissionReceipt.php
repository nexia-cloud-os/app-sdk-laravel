<?php

declare(strict_types=1);

namespace Nexia\Approval;

/** Durable acceptance only. This is not an ApprovalCaseReference or completed submission. */
final readonly class ApprovalSubmissionReceipt
{
    public function __construct(public string $publicId) {}
}
