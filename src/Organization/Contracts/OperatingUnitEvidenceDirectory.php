<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

use Nexia\Organization\OperatingUnitEvidence;

/** Host-owned Operating Unit evidence without exposing a Core persistence model. */
interface OperatingUnitEvidenceDirectory
{
    public function findByKey(int|string $key): ?OperatingUnitEvidence;

    /** @return list<OperatingUnitEvidence> */
    public function effectiveForLegalEntity(int|string $legalEntityKey): array;

    public function lockForLegalEntity(
        string $publicId,
        int|string $legalEntityKey,
    ): ?OperatingUnitEvidence;
}
