<?php

declare(strict_types=1);

namespace Nexia\Process\Domain\Enums;

enum ProcessInstanceStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Incident = 'incident';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Failed], true);
    }
}
