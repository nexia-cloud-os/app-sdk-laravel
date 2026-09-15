<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\ConsumerResult;
use Nexia\Events\EventEnvelope;

interface InboxConsumer
{
    /** @param callable(EventEnvelope): void $handler */
    public function run(EventEnvelope $envelope, string $consumerKey, callable $handler): ConsumerResult;
}
