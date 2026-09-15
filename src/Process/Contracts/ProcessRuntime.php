<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

use Nexia\Process\ProcessInstanceSnapshot;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\Process\ProcessMessageDeliveryResult;
use Nexia\Process\ReceiveTaskCorrelation;

/**
 * App-neutral event-start availability, instance lookup, and activity correlation.
 *
 * The host owns persistence, locking, BPMN validation, and token advancement.
 */
interface ProcessRuntime
{
    /** Whether an executable published definition can consume this event now. */
    public function hasPublishedEventStart(int $legalEntityKey, string $eventName): bool;

    public function findInstance(string $publicId): ?ProcessInstanceSnapshot;

    public function correlateReceiveTask(ReceiveTaskCorrelation $correlation): bool;

    public function deliverMessage(ProcessMessageDelivery $delivery): ProcessMessageDeliveryResult;
}
