<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Optional canonical Party lookup with the same visibility and merge semantics as the single lookup. */
interface BatchCanonicalPartyDirectory
{
    /** @param list<string> $publicIds @return list<Party> Missing or invisible parties are omitted. */
    public function findCanonicalByPublicIds(array $publicIds): array;
}
