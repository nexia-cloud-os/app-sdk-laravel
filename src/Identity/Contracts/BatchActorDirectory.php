<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Optional batch lookup with the same visibility and deletion semantics as findByPublicId. */
interface BatchActorDirectory
{
    /**
     * For directory reads, use `User::whereIn('public_id', $publicIds)->get()->all()`.
     * Keep the host batch when an `Actor[]` is required.
     *
     * @param list<string> $publicIds @return list<Actor>
     * @see \Nexia\Laravel\Directory\User
     */
    public function findByPublicIds(array $publicIds): array;
}
