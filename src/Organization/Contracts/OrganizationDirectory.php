<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

/** Host organization identity lookup without exposing Core models or table columns. */
interface OrganizationDirectory
{
    /**
     * For directory reads, use `LegalEntity::where('public_id', $publicId)->first()`.
     * Use this method when the caller requires the host LegalEntity contract.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function findLegalEntity(string $publicId): ?LegalEntity;

    /**
     * For directory reads, use `LegalEntity::where('public_id', $publicId)->firstOrFail()`.
     * Use this method when the caller requires the host LegalEntity contract.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function legalEntity(string $publicId): LegalEntity;

    /**
     * For directory reads, use `LegalEntity::active()->where('public_id', $publicId)->first()`.
     * The model's `active()` requires an active entity with an organization Party. Core's old active filter
     * checks entity status.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function findActiveLegalEntity(string $publicId): ?LegalEntity;

    /**
     * For directory reads, use `LegalEntity::active()->find($key)`.
     * The model's `active()` requires an active entity with an organization Party. Core's old active filter
     * checks entity status.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function findActiveLegalEntityByKey(int|string $key): ?LegalEntity;

    /**
     * For directory reads, use `LegalEntity::find($key)`.
     * Use this method when the caller requires the host LegalEntity contract.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function findLegalEntityByKey(int|string $key): ?LegalEntity;

    /**
     * For directory reads, use `LegalEntity::whereKey($keys)->get()->all()`.
     * Use this method when the caller requires the host LegalEntity contract.
     *
     * @param list<int|string> $keys @return list<LegalEntity>
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function findLegalEntitiesByKeys(array $keys): array;

    /**
     * For directory reads, use `LegalEntity::findOrFail($key)`.
     * Use this method when the caller requires the host LegalEntity contract.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function requireLegalEntityByKey(int|string $key): LegalEntity;

    /**
     * For directory reads, use `LegalEntity::whereKey($key)->value('public_id')`.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function legalEntityPublicIdByKey(int|string $key): ?string;

    /**
     * For directory reads, use `LegalEntity::where('public_id', $publicId)->when($activeOnly, fn ($q) =>
     * $q->active())->value('key')`.
     * The model's `active()` requires an active entity with an organization Party. Core's old active filter
     * checks entity status.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function legalEntityKeyByPublicId(string $publicId, bool $activeOnly = false): ?int;

    /**
     * For directory reads, use `LegalEntity::whereKey($keys)->pluck('public_id', 'key')->all()`.
     *
     * @param list<int|string> $keys @return array<int|string, string>
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function legalEntityPublicIdsByKeys(array $keys): array;

    /**
     * For the model API, use `$entity->hierarchyRevision()`.
     * On an already-loaded model. Calls the existing host method. Core and Manager currently return `null`.
     *
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function legalEntityHierarchyRevisionByKey(int|string $key): ?int;

    /**
     * For directory reads, use `OperatingUnit::where('public_id', $publicId)->first()`.
     * Use this method when the caller requires the host OperatingUnit contract.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function findOperatingUnit(string $publicId): ?OperatingUnit;

    /**
     * For directory reads, use `OperatingUnit::affiliatedWithLegalEntity($legalEntityKey)->where('public_id',
     * $publicId)->firstOrFail()`.
     * Use this method when the caller requires the host OperatingUnit contract.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function operatingUnit(string $publicId, int|string $legalEntityKey): OperatingUnit;

    /**
     * For display, use `OperatingUnit::affiliatedWithLegalEntity($legalEntityKey)->where('public_id',
     * $publicId)->value('key')`.
     * Use this method when the current host affiliation is required independently of a local snapshot.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function operatingUnitKeyForLegalEntityPublicId(
        int|string $legalEntityKey,
        string $publicId,
    ): ?int;

    /**
     * For directory reads, use `OperatingUnit::whereKey($key)->value('public_id')`.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function operatingUnitPublicIdByKey(int|string $key): ?string;

    /**
     * For directory reads, use `OperatingUnit::where('public_id', $publicId)->value('key')`.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function operatingUnitKeyByPublicId(string $publicId): ?int;

    /**
     * For directory reads, use `OperatingUnit::whereKey($keys)->pluck('public_id', 'key')->all()`.
     *
     * @param list<int|string> $keys @return array<int|string, string>
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function operatingUnitPublicIdsByKeys(array $keys): array;

    /**
     * For directory reads, use `OperatingUnit::find($key)`.
     * Use this method when the caller requires the host OperatingUnit contract.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function findOperatingUnitByKey(int|string $key): ?OperatingUnit;

    /**
     * For directory reads, use `OperatingUnit::whereKey($keys)->get()->all()`.
     * Use this method when the caller requires the host OperatingUnit contract.
     *
     * @param list<int|string> $keys @return list<OperatingUnit>
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function findOperatingUnitsByKeys(array $keys): array;

    /**
     * For directory reads, use `OperatingUnit::findOrFail($key)`.
     * Use this method when the caller requires the host OperatingUnit contract.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function requireOperatingUnitByKey(int|string $key): OperatingUnit;

    /**
     * For display, use `OperatingUnit::affiliatedWithLegalEntity($legalEntityKey)->whereKey($operatingUnitKey)->exists()`.
     * Keep the host check when validating affiliation before a write.
     *
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function isOperatingUnitAffiliatedWithLegalEntity(
        int|string $operatingUnitKey,
        int|string $legalEntityKey,
    ): bool;

    /**
     * For directory reads, use `OperatingUnit::affiliatedWithLegalEntity($legalEntityKey)->pluck('key')->all()`.
     * Use the host method for the full affiliation key list regardless of local snapshot contents.
     *
     * @return list<int>
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function operatingUnitKeysForLegalEntity(int|string $legalEntityKey): array;

    /**
     * For directory reads, use `LegalEntity::orderBy('code')->get()->all()`.
     * Keep the authorized target resolver for selectable entities.
     *
     * @return list<LegalEntity>
     * @see \Nexia\Laravel\Directory\LegalEntity
     */
    public function legalEntities(): array;

    /**
     * For directory reads, use `OperatingUnit::forLegalEntity($legalEntityKey,
     * $asOf)->orderBy('display_label')->get()->all()`.
     * Checks effectiveness on the given date. The affiliation scope checks affiliation.
     *
     * @return list<OperatingUnit>
     * @see \Nexia\Laravel\Directory\OperatingUnit
     */
    public function effectiveOperatingUnitsForLegalEntity(int|string $legalEntityKey, ?string $asOf = null): array;
}
