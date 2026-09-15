<?php

declare(strict_types=1);

namespace Nexia\Laravel\Identity\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Nexia\Identity\Contracts\Party;
use Nexia\Identity\PersonDirectoryEntry;

/** Host Party lookup and query composition without exposing the Core Party model. */
interface PartyDirectory
{
    public function findByPublicId(string $publicId): ?Party;

    /** Resolve a public ID through merge lineage to its surviving Party. */
    public function findCanonicalByPublicId(string $publicId): ?Party;

    public function requireByPublicId(string $publicId): Party;

    public function requireByPublicIdForUpdate(string $publicId): Party;

    public function exists(string $publicId): bool;

    /**
     * @param  list<string>  $publicIds
     * @return array<string, Party> canonical lowercase public ID => Party
     */
    public function findByPublicIds(array $publicIds): array;

    public function findByKey(int|string $key): ?Party;

    /** @param list<int|string> $keys @return list<Party> */
    public function findByKeys(array $keys): array;

    public function requireByKey(int|string $key): Party;

    public function publicIdByKey(int|string $key): ?string;

    /** @param list<int|string> $keys @return array<int|string, string> */
    public function publicIdsByKeys(array $keys): array;

    /** @return list<Party> */
    public function activeSearch(string $search, int $limit): array;

    /** @return list<Party> zero, one, or multiple active person matches */
    public function activePeopleByEmail(string $email, int $limit = 2): array;

    /** @param list<int|string> $excludedKeys @return list<PersonDirectoryEntry> */
    public function searchActivePeople(string $search, int $limit, array $excludedKeys = []): array;

    public function orderByDisplayLabel(Builder $query, string $partyKeyColumn, string $direction = 'asc'): Builder;

    public function whereDisplayLabelMatches(Builder $query, string $partyKeyColumn, string $search): Builder;
}
