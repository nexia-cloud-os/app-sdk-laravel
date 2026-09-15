<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonLoginInvitationStatus;

/** Optional bulk invitation-status lookup capability. */
interface BatchPersonLoginInvitations
{
    /**
     * @param  list<array{email:string,person_party_public_id:string}>  $people
     * @return array<string, PersonLoginInvitationStatus> normalized email => status
     */
    public function statuses(
        Actor $actor,
        array $people,
        int|string $legalEntityKey,
    ): array;
}
