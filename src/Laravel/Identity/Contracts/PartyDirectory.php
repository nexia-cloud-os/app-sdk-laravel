<?php

declare(strict_types=1);

namespace Nexia\Laravel\Identity\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Nexia\Identity\Contracts\Party;
use Nexia\Identity\PersonDirectoryEntry;

/** Host Party lookup and query composition without exposing the Core Party model. */
interface PartyDirectory
{
    /**
     * For directory reads, use `Party::where('public_id', $publicId)->first()`.
     * Use this method when the caller requires the host Party contract or caller-specific visibility.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findByPublicId(string $publicId): ?Party;

    /**
     * Resolve a public ID through merge lineage to its surviving Party.
     *
     * For the model API, use `Party::canonical($publicId)`.
     * Uses the host to follow merges. Returns a model with basic fields.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findCanonicalByPublicId(string $publicId): ?Party;

    /**
     * For directory reads, use `Party::where('public_id', $publicId)->firstOrFail()`.
     * Use this method when the caller requires the host Party contract or caller-specific visibility.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function requireByPublicId(string $publicId): Party;

    /**
     * For directory reads, use `Party::where('public_id', $publicId)->exists()`.
     * For access decisions, use `PartyAccess::allows()`.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function exists(string $publicId): bool;

    /**
     * For directory reads, use `Party::whereIn('public_id', $publicIds)->get()->keyBy('public_id')->all()`.
     * Keeps the public-ID map. Values are Eloquent models.
     *
     * @param  list<string>  $publicIds
     * @return array<string, Party> canonical lowercase public ID => Party
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findByPublicIds(array $publicIds): array;

    /**
     * For directory reads, use `Party::find($key)`.
     * Use this method when the caller requires the host Party contract or caller-specific visibility.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findByKey(int|string $key): ?Party;

    /**
     * For directory reads, use `Party::whereKey($keys)->get()->all()`.
     * Use this method when the caller requires the host Party contract or caller-specific visibility.
     *
     * @param list<int|string> $keys @return list<Party>
     * @see \Nexia\Laravel\Directory\Party
     */
    public function findByKeys(array $keys): array;

    /**
     * For directory reads, use `Party::findOrFail($key)`.
     * Use this method when the caller requires the host Party contract or caller-specific visibility.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function requireByKey(int|string $key): Party;

    /**
     * For directory reads, use `Party::whereKey($key)->value('public_id')`.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function publicIdByKey(int|string $key): ?string;

    /**
     * For directory reads, use `Party::whereKey($keys)->pluck('public_id', 'key')->all()`.
     *
     * @param list<int|string> $keys @return array<int|string, string>
     * @see \Nexia\Laravel\Directory\Party
     */
    public function publicIdsByKeys(array $keys): array;

    /**
     * For a basic directory list, use Party::active() and add matchingName($search) for a nonempty search.
     * Keep this method when search must respect caller-specific visibility.
     *
     * @return list<Party>
     * @see \Nexia\Laravel\Directory\Party
     */
    public function activeSearch(string $search, int $limit): array;

    /**
     * For the model API, use `Party::peopleByEmail($email, $limit)`.
     * Uses the same host lookup and returns a collection of models.
     *
     * @return list<Party> zero, one, or multiple active person matches
     * @see \Nexia\Laravel\Directory\Party
     */
    public function activePeopleByEmail(string $email, int $limit = 2): array;

    /**
     * For the model API, use `Party::searchPeople($search, $limit, $excludedKeys)`.
     * Keeps email search and exclusions. Returns models with `contact_email` in place of `PersonDirectoryEntry`
     * objects.
     *
     * @param list<int|string> $excludedKeys @return list<PersonDirectoryEntry>
     * @see \Nexia\Laravel\Directory\Party
     */
    public function searchActivePeople(string $search, int $limit, array $excludedKeys = []): array;

    /**
     * For a basic directory list, join the Party model with leftJoinSub() and order by display_name.
     * Keep this helper for caller-specific visibility or a query on a separate database connection.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function orderByDisplayLabel(Builder $query, string $partyKeyColumn, string $direction = 'asc'): Builder;

    /**
     * For a basic directory list, use Party::matchingName($search)->select('key') as the App query's whereIn()
     * subquery.
     * Keep this helper for caller-specific visibility or a query on a separate database connection.
     *
     * @see \Nexia\Laravel\Directory\Party
     */
    public function whereDisplayLabelMatches(Builder $query, string $partyKeyColumn, string $search): Builder;
}
