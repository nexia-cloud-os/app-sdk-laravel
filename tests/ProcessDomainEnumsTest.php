<?php

declare(strict_types=1);

use Nexia\Process\Domain\Enums\DecisionHitPolicy;
use Nexia\Process\Domain\Enums\ProcessInstanceStatus;
use Nexia\Process\Domain\Enums\ProcessMessageDeliveryStatus;

require dirname(__DIR__).'/vendor/autoload.php';

$expected = [
    DecisionHitPolicy::class => ['UNIQUE', 'FIRST', 'ANY', 'COLLECT', 'COLLECT_SUM', 'COLLECT_MIN', 'COLLECT_MAX', 'COLLECT_COUNT'],
    ProcessInstanceStatus::class => ['running', 'completed', 'cancelled', 'failed', 'incident'],
    ProcessMessageDeliveryStatus::class => ['consumed', 'pending', 'already_consumed', 'rejected'],
];

foreach ($expected as $enum => $values) {
    if (array_map(static fn (BackedEnum $case): string => $case->value, $enum::cases()) !== $values) {
        throw new RuntimeException("Process domain enum [{$enum}] changed unexpectedly.");
    }
}

if (! ProcessInstanceStatus::Completed->isTerminal()
    || ! ProcessInstanceStatus::Cancelled->isTerminal()
    || ! ProcessInstanceStatus::Failed->isTerminal()
    || ProcessInstanceStatus::Running->isTerminal()
    || ProcessInstanceStatus::Incident->isTerminal()
) {
    throw new RuntimeException('Process instance terminal-state contract changed unexpectedly.');
}

fwrite(STDOUT, "Process domain enum contracts passed.\n");
