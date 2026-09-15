<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** App-neutral lifecycle state of one immutable signature request. */
enum SignatureRequestStatus: string
{
    case Draft = 'draft';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Dispatching = 'dispatching';
    case InProgress = 'in_progress';
    case Finalizing = 'finalizing';
    case Completed = 'completed';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
