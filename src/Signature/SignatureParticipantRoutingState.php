<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Log-safe routing eligibility and progress of one request participant. */
enum SignatureParticipantRoutingState: string
{
    case Waiting = 'waiting';
    case RevisionPending = 'revision_pending';
    case Active = 'active';
    case Completed = 'completed';
    case Closed = 'closed';
    case Failed = 'failed';
}
