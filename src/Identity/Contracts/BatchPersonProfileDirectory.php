<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonProfileView;

/** Optional bulk person-profile lookup capability. */
interface BatchPersonProfileDirectory
{
    /** @param list<int|string> $partyKeys @return array<int|string, PersonProfileView> */
    public function findByPartyKeys(array $partyKeys): array;
}
