<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonProfileView;

interface PersonProfileDirectory
{
    public function findByPartyKey(int|string $partyKey): ?PersonProfileView;
}
