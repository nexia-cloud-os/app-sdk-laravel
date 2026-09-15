<?php

declare(strict_types=1);

namespace Nexia\Security\Contracts;

use Nexia\Identity\Contracts\Actor;

/** Host-owned recent-authentication proof for a protected App action. */
interface RecentAuthentication
{
    public function isFresh(Actor $actor, int $withinMinutes = 15): bool;
}
