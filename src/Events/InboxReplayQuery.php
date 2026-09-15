<?php

declare(strict_types=1);

namespace Nexia\Events;

use InvalidArgumentException;

/** App-neutral lookup for an envelope already durable in the tenant Inbox. */
final readonly class InboxReplayQuery
{
    public function __construct(
        public string $consumerKey,
        public string $eventName,
        public string $aggregateType,
        public string $aggregateId,
    ) {
        foreach ([$consumerKey, $eventName, $aggregateType, $aggregateId] as $value) {
            if ($value === '' || $value !== trim($value) || strlen($value) > 191) {
                throw new InvalidArgumentException('Inbox replay query identity is invalid.');
            }
        }
    }
}
