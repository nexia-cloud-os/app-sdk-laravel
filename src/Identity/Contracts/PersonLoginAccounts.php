<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PersonLoginInvitationAccessProfile;

/** Prepare a person's login identity independently of sending an invitation. */
interface PersonLoginAccounts
{
    /**
     * Idempotently create or reuse the exact person's account. Never send mail or
     * replace an existing password. With no access profile, new accounts remain
     * deactivated and existing accounts retain their lifecycle and authority.
     * Operating units add participation only within the selected legal entity; existing
     * memberships and roles are preserved. No units are attached without an access profile.
     * @param list<string> $operatingUnitPublicIds
     * The host rechecks user-create authority in the selected legal entity.
     */
    public function ensure(Actor $actor, string $email, string $personPartyPublicId,
        int|string $legalEntityKey, ?PersonLoginInvitationAccessProfile $accessProfile = null, array $operatingUnitPublicIds = []): void;
}
