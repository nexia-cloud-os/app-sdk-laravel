<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

/** Host organization identity lookup without exposing Core models or table columns. */
interface OrganizationDirectory
{
    public function findLegalEntity(string $publicId): ?LegalEntity;

    public function legalEntity(string $publicId): LegalEntity;

    public function findActiveLegalEntity(string $publicId): ?LegalEntity;

    public function findActiveLegalEntityByKey(int|string $key): ?LegalEntity;

    public function findLegalEntityByKey(int|string $key): ?LegalEntity;

    /** @param list<int|string> $keys @return list<LegalEntity> */
    public function findLegalEntitiesByKeys(array $keys): array;

    public function requireLegalEntityByKey(int|string $key): LegalEntity;

    public function lockLegalEntityByKey(int|string $key): LegalEntity;

    public function legalEntityPublicIdByKey(int|string $key): ?string;

    public function legalEntityKeyByPublicId(string $publicId, bool $activeOnly = false): ?int;

    /** @param list<int|string> $keys @return array<int|string, string> */
    public function legalEntityPublicIdsByKeys(array $keys): array;

    public function legalEntityHierarchyRevisionByKey(int|string $key): ?int;

    public function findOperatingUnit(string $publicId): ?OperatingUnit;

    public function operatingUnit(string $publicId, int|string $legalEntityKey): OperatingUnit;

    public function lockOperatingUnit(string $publicId, int|string $legalEntityKey): ?OperatingUnit;

    public function operatingUnitKeyForLegalEntityPublicId(
        int|string $legalEntityKey,
        string $publicId,
    ): ?int;

    public function operatingUnitPublicIdByKey(int|string $key): ?string;

    public function operatingUnitKeyByPublicId(string $publicId): ?int;

    /** @param list<int|string> $keys @return array<int|string, string> */
    public function operatingUnitPublicIdsByKeys(array $keys): array;

    public function findOperatingUnitByKey(int|string $key): ?OperatingUnit;

    /** @param list<int|string> $keys @return list<OperatingUnit> */
    public function findOperatingUnitsByKeys(array $keys): array;

    public function requireOperatingUnitByKey(int|string $key): OperatingUnit;

    public function isOperatingUnitAffiliatedWithLegalEntity(
        int|string $operatingUnitKey,
        int|string $legalEntityKey,
    ): bool;

    /** @return list<int> */
    public function operatingUnitKeysForLegalEntity(int|string $legalEntityKey): array;

    /** @return list<LegalEntity> */
    public function legalEntities(): array;

    /** @return list<OperatingUnit> */
    public function effectiveOperatingUnitsForLegalEntity(int|string $legalEntityKey, ?string $asOf = null): array;
}
