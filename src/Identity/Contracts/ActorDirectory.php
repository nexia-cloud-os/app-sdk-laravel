<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Host actor lookup without exposing the Core user model or query builder. */
interface ActorDirectory
{
    public function findByKey(int|string|null $key, bool $includeDeleted = false): ?Actor;

    /** @param list<int|string> $keys @return list<Actor> */
    public function findByKeys(array $keys, bool $includeDeleted = false): array;

    public function requireByKey(int|string $key, bool $includeDeleted = false): Actor;

    public function findByPublicId(string $publicId): ?Actor;

    public function requireByPublicId(string $publicId): Actor;

    public function findByPartyKey(int|string $partyKey): ?Actor;

    public function keyByPublicId(string $publicId): int|string|null;

    public function publicIdByKey(int|string $key): ?string;

    /** @param list<int|string> $keys @return array<int|string, string> */
    public function publicIdsByKeys(array $keys): array;

    public function displayLabelByKey(int|string $key): ?string;

    public function activeByPublicIdForLegalEntity(
        string $publicId,
        int|string $legalEntityKey,
    ): ?Actor;

    public function existsByKey(int|string $key, bool $includeDeleted = false): bool;

    /** @return list<Actor> */
    public function activeForLegalEntity(int|string $legalEntityKey): array;
}
