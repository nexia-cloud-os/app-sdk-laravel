<?php

declare(strict_types=1);

namespace Nexia\DataMigration;

use InvalidArgumentException;

/** Stable identity and host context for a migration execution. */
final readonly class DataMigrationRunStart
{
    public function __construct(
        public string $stageKey,
        public string $providerKey,
        public string $targetKey,
        public ?string $legalEntityPublicId = null,
        public ?string $actorPublicId = null,
        public ?string $operationKey = null,
    ) {
        foreach ([
            'stageKey' => $stageKey,
            'providerKey' => $providerKey,
            'targetKey' => $targetKey,
        ] as $name => $key) {
            if (strlen($key) > 120
                || preg_match('/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/D', $key) !== 1) {
                throw new InvalidArgumentException("Data migration {$name} is not a stable key.");
            }
        }
        if ($operationKey !== null && (trim($operationKey) === '' || strlen($operationKey) > 191)) {
            throw new InvalidArgumentException('Data migration operationKey must be non-empty and at most 191 bytes.');
        }
    }
}
