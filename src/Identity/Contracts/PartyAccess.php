<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Host-evaluated row visibility for Party-backed data. */
interface PartyAccess
{
    public function allows(Actor $actor, int|string|null $partyKey): bool;
}
