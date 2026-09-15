<?php

declare(strict_types=1);

namespace Nexia\Approval;

final readonly class ApprovalCaseReference
{
    public function __construct(public string $publicId) {}
}
