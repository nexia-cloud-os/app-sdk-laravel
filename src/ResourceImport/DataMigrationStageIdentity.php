<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use InvalidArgumentException;

/** Server-owned Setup evidence identity for a successful import pipeline. */
final readonly class DataMigrationStageIdentity
{
    public function __construct(
        public string $stageKey,
        public string $providerKey,
        public string $targetKey,
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
    }

    /** @return array{stage_key: string, provider_key: string, target_key: string} */
    public function toArray(): array
    {
        return [
            'stage_key' => $this->stageKey,
            'provider_key' => $this->providerKey,
            'target_key' => $this->targetKey,
        ];
    }
}
