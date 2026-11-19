<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Optional canonical Party lookup with the same visibility and merge semantics as the single lookup. */
interface BatchCanonicalPartyDirectory
{
    /**
     * For the model API, use `Party::canonicalMany($publicIds)`.
     * Uses batch lookup when available and individual lookups otherwise. Returns a model collection.
     *
     * @param list<string> $publicIds @return list<Party> Missing or invisible parties are omitted.
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findCanonicalByPublicIds(array $publicIds): array;
}
