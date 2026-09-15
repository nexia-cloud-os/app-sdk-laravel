<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Enums;

enum ApprovalState: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Recalled = 'recalled';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Recalled, self::Cancelled], true);
    }
}
