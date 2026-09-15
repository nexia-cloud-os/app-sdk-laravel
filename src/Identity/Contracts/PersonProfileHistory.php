<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonProfileHistoryPage;

/** App-neutral read boundary for the Core-owned person-profile change history. */
interface PersonProfileHistory
{
    public function pageForAuthorizedHr(
        Actor $actor,
        int|string $partyKey,
        int|string $legalEntityKey,
        ?string $cursor = null,
        int $limit = 20,
    ): PersonProfileHistoryPage;
}
