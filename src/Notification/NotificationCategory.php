<?php

declare(strict_types=1);

namespace Nexia\Notification;

use InvalidArgumentException;

/** App-neutral category metadata for the shared notification preference catalog. */
final readonly class NotificationCategory
{
    public function __construct(
        public string $key,
        public string $labelKey,
        public int $order,
        public string $variant = 'neutral',
        public string $icon = 'default',
    ) {
        if (! preg_match('/\A[a-z][a-z0-9._-]*\z/D', $key)
            || trim($labelKey) === ''
            || $order < 0) {
            throw new InvalidArgumentException('Notification category metadata is invalid.');
        }
    }
}
