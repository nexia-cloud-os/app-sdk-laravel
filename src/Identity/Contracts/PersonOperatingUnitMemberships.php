<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Change an existing person's organization participation without provisioning identity or authority. */
interface PersonOperatingUnitMemberships
{
    /**
     * Move only the named source participation, preserving all other memberships and grants.
     * Null source adds participation; null target removes it. Suspended/invited participation
     * requires administrative review. The host enforces membership permissions in the company.
     * sourceReference is an immutable idempotency key: retry with identical arguments.
     * @return array{status: string, user_public_id: ?string}
     */
    public function move(Actor $actor, string $personPartyPublicId, int|string $legalEntityKey,
        ?string $fromUnitPublicId, ?string $toUnitPublicId, bool $makePrimary, string $sourceReference): array;
}
