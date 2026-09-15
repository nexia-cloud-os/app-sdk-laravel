<?php

declare(strict_types=1);

namespace Nexia\Signature;

use LogicException;

/** App-neutral terminal fact emitted after an immutable signature request ends. */
enum SignatureRequestOutcome: string
{
    case Completed = 'completed';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public static function fromRequestStatus(SignatureRequestStatus $status): self
    {
        return match ($status) {
            SignatureRequestStatus::Completed => self::Completed,
            SignatureRequestStatus::Declined => self::Declined,
            SignatureRequestStatus::Expired => self::Expired,
            SignatureRequestStatus::Cancelled => self::Cancelled,
            SignatureRequestStatus::Failed => self::Failed,
            default => throw new LogicException('Only terminal SignatureRequest statuses have an outcome.'),
        };
    }
}
