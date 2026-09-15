<?php

declare(strict_types=1);

namespace Nexia\SelfService;

enum SelfServiceActionStage: string
{
    case ActionRequired = 'ACTION_REQUIRED';
    case ChangesRequested = 'CHANGES_REQUESTED';
    case Draft = 'DRAFT';
    case CompanyReviewing = 'COMPANY_REVIEWING';
    case ApprovalInProgress = 'APPROVAL_IN_PROGRESS';
    case Processing = 'PROCESSING';
    case Completed = 'COMPLETED';
    case Failed = 'FAILED';
}
