<?php

declare(strict_types=1);

namespace Nexia\Process\Domain\Enums;

enum ProcessMessageDeliveryStatus: string
{
    case Consumed = 'consumed';
    case Pending = 'pending';
    case AlreadyConsumed = 'already_consumed';
    case Rejected = 'rejected';
}
