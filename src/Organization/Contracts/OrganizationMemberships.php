<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

use Nexia\Identity\Contracts\Actor;

/** Participation lookup kept separate from authorization grants. */
interface OrganizationMemberships
{
    public function actorParticipatesInLegalEntity(Actor $actor, int|string $legalEntityKey): bool;
}
