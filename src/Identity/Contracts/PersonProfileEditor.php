<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonProfilePatch;
use Nexia\Identity\PersonProfileView;

/** App-neutral command boundary for the Core-owned person Party profile. */
interface PersonProfileEditor
{
    public function updateForAuthorizedHr(
        Actor $actor,
        int|string $partyKey,
        int|string $legalEntityKey,
        PersonProfilePatch $patch,
    ): PersonProfileView;

    /** Exact-self command: only contact email, phone and address are applied. */
    public function updateOwnContact(
        Actor $actor,
        int|string $partyKey,
        PersonProfilePatch $patch,
    ): PersonProfileView;
}
