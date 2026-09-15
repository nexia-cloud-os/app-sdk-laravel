<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventEnvelope;
use Nexia\Identity\Contracts\Actor;

interface EventActorAuthorizer
{
    public function allows(Actor $actor, EventEnvelope $envelope): bool;
}
