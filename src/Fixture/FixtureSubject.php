<?php

declare(strict_types=1);

namespace Nexia\Fixture;

use InvalidArgumentException;

/** Host-owned actor and organization identity exposed without Core models. */
final readonly class FixtureSubject
{
    public function __construct(
        public string $key,
        public int $sequence,
        public int $userId,
        public string $userPublicId,
        public int $partyId,
        public string $partyPublicId,
        public string $displayName,
        public ?int $operatingUnitId = null,
        public ?string $operatingUnitPublicId = null,
        public ?string $operatingUnitName = null,
    ) {
        if (trim($this->key) === ''
            || $this->sequence < 1
            || $this->userId < 1
            || trim($this->userPublicId) === ''
            || $this->partyId < 1
            || trim($this->partyPublicId) === ''
            || trim($this->displayName) === ''
        ) {
            throw new InvalidArgumentException('Fixture subject identity must be complete.');
        }

        $operatingUnitFields = [
            $this->operatingUnitId,
            $this->operatingUnitPublicId,
            $this->operatingUnitName,
        ];
        $present = array_filter($operatingUnitFields, static fn (mixed $value): bool => $value !== null);

        if ($present !== [] && count($present) !== count($operatingUnitFields)) {
            throw new InvalidArgumentException('Fixture subject Operating Unit identity must be complete when supplied.');
        }
    }
}
