<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\PartyRelationship;

/** Actor-filtered lookup for host-owned Party relationships. */
interface PartyRelationshipDirectory
{
    public function findVisibleByPublicId(Actor $actor, string $publicId): ?PartyRelationship;
}
