<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Optional batch lookup with the same visibility and deletion semantics as findByPublicId. */
interface BatchActorDirectory
{
    /** @param list<string> $publicIds @return list<Actor> */
    public function findByPublicIds(array $publicIds): array;
}
