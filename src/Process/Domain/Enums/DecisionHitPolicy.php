<?php

declare(strict_types=1);

namespace Nexia\Process\Domain\Enums;

enum DecisionHitPolicy: string
{
    case Unique = 'UNIQUE';
    case First = 'FIRST';
    case Any = 'ANY';
    case Collect = 'COLLECT';
    case CollectSum = 'COLLECT_SUM';
    case CollectMin = 'COLLECT_MIN';
    case CollectMax = 'COLLECT_MAX';
    case CollectCount = 'COLLECT_COUNT';
}
