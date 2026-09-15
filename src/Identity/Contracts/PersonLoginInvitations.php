<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonLoginInvitationAccessProfile;
use Nexia\Identity\PersonLoginInvitationStatus;

/** Core-owned login invitation boundary for an existing person Party. */
interface PersonLoginInvitations
{
    public function status(
        Actor $actor,
        string $email,
        string $personPartyPublicId,
        int|string $legalEntityKey,
    ): PersonLoginInvitationStatus;

    public function invite(
        Actor $actor,
        string $email,
        string $personPartyPublicId,
        int|string $legalEntityKey,
        ?PersonLoginInvitationAccessProfile $accessProfile = null,
    ): PersonLoginInvitationStatus;
}
