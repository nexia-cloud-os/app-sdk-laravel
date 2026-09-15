<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventEnvelope;

interface Outbox
{
    public function write(
        string $eventName,
        string $aggregateType,
        string $aggregateId,
        array $payload,
        ?string $correlationId = null,
        ?string $causationId = null,
        array $headers = [],
    ): object;

    public function writeEnvelope(EventEnvelope $envelope): object;
}
