<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventDraft;

/** Core-owned event publication boundary for App event intent. */
interface EventPublisher
{
    public function publish(EventDraft $draft): void;
}
