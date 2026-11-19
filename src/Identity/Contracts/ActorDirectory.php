<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Host actor lookup without exposing the Core user model or query builder. */
interface ActorDirectory
{
    /**
     * For directory reads, use `$key === null ? null : User::withTrashed($includeDeleted)->find($key)`.
     * Use this method when the caller requires the host Actor contract.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function findByKey(int|string|null $key, bool $includeDeleted = false): ?Actor;

    /**
     * For directory reads, use `User::withTrashed($includeDeleted)->whereKey($keys)->get()->all()`.
     * Use this method when the caller requires the host Actor contract.
     *
     * @param list<int|string> $keys @return list<Actor>
     * @see \Nexia\Laravel\Directory\User
     */
    public function findByKeys(array $keys, bool $includeDeleted = false): array;

    /**
     * For directory reads, use `User::withTrashed($includeDeleted)->findOrFail($key)`.
     * Use this method when the caller requires the host Actor contract.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function requireByKey(int|string $key, bool $includeDeleted = false): Actor;

    /**
     * For directory reads, use `User::where('public_id', $publicId)->first()`.
     * Use this method when the caller requires the host Actor contract.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function findByPublicId(string $publicId): ?Actor;

    /**
     * For directory reads, use `User::where('public_id', $publicId)->firstOrFail()`.
     * Use this method when the caller requires the host Actor contract.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function requireByPublicId(string $publicId): Actor;

    /**
     * For directory reads, use `User::where('party_key', $partyKey)->orderBy('key')->first()`.
     * Ascending key preserves the first-account selection.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function findByPartyKey(int|string $partyKey): ?Actor;

    /**
     * For directory reads, use `User::where('public_id', $publicId)->value('key')`.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function keyByPublicId(string $publicId): int|string|null;

    /**
     * For directory reads, use `User::whereKey($key)->value('public_id')`.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function publicIdByKey(int|string $key): ?string;

    /**
     * For directory reads, use `User::whereKey($keys)->pluck('public_id', 'key')->all()`.
     *
     * @param list<int|string> $keys @return array<int|string, string>
     * @see \Nexia\Laravel\Directory\User
     */
    public function publicIdsByKeys(array $keys): array;

    /**
     * For directory reads, use `User::whereKey($key)->value('display_label')`.
     * The model falls back to the public ID when the Party label and user name are missing. The host may return
     * the email.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function displayLabelByKey(int|string $key): ?string;

    /**
     * For display, use `User::activeForLegalEntity($legalEntityKey)->where('public_id', $publicId)->first()`.
     * The scope reads the active actor list and applies its keys to SQL. Keep the exact host
     * lookup for current eligibility, such as signer checks.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function activeByPublicIdForLegalEntity(
        string $publicId,
        int|string $legalEntityKey,
    ): ?Actor;

    /**
     * For directory reads, use `User::withTrashed($includeDeleted)->whereKey($key)->exists()`.
     *
     * @see \Nexia\Laravel\Directory\User
     */
    public function existsByKey(int|string $key, bool $includeDeleted = false): bool;

    /**
     * For directory reads, use
     * `User::activeForLegalEntity($legalEntityKey)->orderBy('display_label')->orderBy('key')->get()->all()`.
     * Returns User models. SQL ordering can differ from the host's natural sort.
     *
     * @return list<Actor>
     * @see \Nexia\Laravel\Directory\User
     */
    public function activeForLegalEntity(int|string $legalEntityKey): array;
}
