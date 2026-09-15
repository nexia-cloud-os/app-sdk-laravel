<?php

declare(strict_types=1);

namespace Nexia\Events;

use InvalidArgumentException;

/** Stable recovery metadata attached to an App event listener registration. */
final readonly class EventConsumerRegistration
{
    public function __construct(
        public string $consumerKey,
        public EventRecoveryMode $recoveryMode,
        public int $supportedSchemaVersion = 1,
        public EventSubscriptionMode $subscriptionMode = EventSubscriptionMode::Exact,
    ) {
        if (mb_strlen($consumerKey) > 160
            || preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9._-]*\z/D', $consumerKey) !== 1) {
            throw new InvalidArgumentException('Event consumers require a canonical stable key owned by an App.');
        }

        if ($supportedSchemaVersion < 1) {
            throw new InvalidArgumentException('Event consumers require a positive supported schema version.');
        }
    }
}
