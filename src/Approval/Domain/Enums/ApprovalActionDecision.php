<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Enums;

enum ApprovalActionDecision: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Consult = 'consult';
    case Object = 'object';
}
