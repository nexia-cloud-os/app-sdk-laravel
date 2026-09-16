<?php

declare(strict_types=1);

namespace Nexia\Notification\Contracts;

use Nexia\Events\EventEnvelope;
use Nexia\Notification\NotificationPublication;

/** Core-owned durable fan-out boundary for an App's consumed business event. */
interface NotificationPublisher
{
    public function publish(EventEnvelope $source, NotificationPublication $publication): void;
}
