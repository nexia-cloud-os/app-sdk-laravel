<?php

declare(strict_types=1);

namespace Nexia\DataMigration;

use DateTimeImmutable;

/** App-neutral projection of the Core-owned execution record. */
final readonly class DataMigrationRunRecord
{
    public function __construct(
        public string $publicId,
        public string $stageKey,
        public string $providerKey,
        public string $targetKey,
        public DataMigrationRunStatus $status,
        public ?string $legalEntityPublicId,
        public ?DataMigrationRunResult $result,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt = null,
        public ?DateTimeImmutable $failedAt = null,
    ) {}

    /**
     * @return array{
     *   public_id: string,
     *   stage_key: string,
     *   provider_key: string,
     *   target_key: string,
     *   status: string,
     *   legal_entity_public_id: string|null,
     *   result: array<string, bool|int|float|string|null>|null,
     *   started_at: string,
     *   completed_at: string|null,
     *   failed_at: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'stage_key' => $this->stageKey,
            'provider_key' => $this->providerKey,
            'target_key' => $this->targetKey,
            'status' => $this->status->value,
            'legal_entity_public_id' => $this->legalEntityPublicId,
            'result' => $this->result?->metadata,
            'started_at' => $this->startedAt->format(DATE_ATOM),
            'completed_at' => $this->completedAt?->format(DATE_ATOM),
            'failed_at' => $this->failedAt?->format(DATE_ATOM),
        ];
    }
}
