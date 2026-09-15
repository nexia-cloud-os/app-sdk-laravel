<?php

declare(strict_types=1);

namespace Nexia\Tests\Fixtures\OperationsFixture\Enums;

enum WorkTicketStatus: string
{
    case Draft = 'DRAFT';
    case Sent = 'SENT';
    case Recorded = 'RECORDED';
    case Cancelled = 'CANCELLED';
}
