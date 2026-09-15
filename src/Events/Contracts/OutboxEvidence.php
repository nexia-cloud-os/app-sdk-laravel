<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

/** Read-only verification of an event that was durably recorded in the tenant outbox. */
interface OutboxEvidence
{
    public function exists(
        int|string $outboxKey,
        string $eventName,
        string $aggregateType,
        string $aggregateId,
    ): bool;
}
