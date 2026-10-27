<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonProfileHistoryPage;
use Nexia\ResourceReference\ResourceRef;

/** App-neutral read boundary for the Core-owned person-profile change history. */
interface PersonProfileHistory
{
    /**
     * An optional owner resource is re-resolved with purpose {app}.person-profile-history.
     * The resource descriptor must declare fieldSchema.person_profile_history.read_permission
     * as an active, owner-app LegalEntity permission. The host requires its LegalEntity population.
     * Its owner must authorize the actor for the exact record and legal entity and return
     * fields.person_profile_history = {party_key: string, legal_entity_key: string}.
     * The reference itself is identity, never a grant. Without it directory authority is required.
     */
    public function pageForAuthorizedHr(
        Actor $actor,
        int|string $partyKey,
        int|string $legalEntityKey,
        ?string $cursor = null,
        int $limit = 20,
        ?ResourceRef $authorizationResource = null,
    ): PersonProfileHistoryPage;
}
