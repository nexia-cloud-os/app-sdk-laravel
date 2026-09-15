<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** App-neutral progress of one request participant; it never reveals recipient contact data. */
enum SignatureParticipantStatus: string
{
    case Pending = 'pending';
    case Invited = 'invited';
    case InProgress = 'in_progress';
    case Authenticated = 'authenticated';
    case Consented = 'consented';
    case Signed = 'signed';
    case Completed = 'completed';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
