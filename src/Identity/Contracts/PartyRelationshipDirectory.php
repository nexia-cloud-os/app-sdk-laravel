<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PartyRelationship;

/** Actor-filtered lookup for host-owned Party relationships. */
interface PartyRelationshipDirectory
{
    /**
     * For the model API, use `Party::relationship($actor, $publicId)`.
     * Uses the same actor-aware host lookup and returns the same nullable DTO.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findVisibleByPublicId(Actor $actor, string $publicId): ?PartyRelationship;
}
