<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use Nexia\Attachments\AttachmentTarget;

/**
 * What the host may know about one import batch.
 *
 * Deliberately a report rather than a handle: the host reads counts and rows to
 * render a preview and decide whether to offer an approval, and does nothing
 * else with the batch. Everything that mutates it goes back through the pipeline.
 */
final class ImportBatchState
{
    /**
     * @param  string  $batchId  the app's own public identifier for the batch
     * @param  bool  $applyEligible  whether `apply()` may run from here, declared
     *                               by the adapter rather than inferred from
     *                               `$stage` — one app treats a failed batch as
     *                               eligible so a retry can finish it
     * @param  int|null  $skippedCount  rows skipped as already present, or null
     *                                  where the app has no duplicate concept.
     *                                  A consumer must distinguish null from 0:
     *                                  rendering null as "0 skipped" asserts a
     *                                  duplicate check happened and found
     *                                  nothing, which would be a lie
     * @param  list<array{row: int, fields: list<string>}>  $rejectedRows  which
     *                                                                     rows the app refused and which fields
     *                                                                     caused it — the app's judgement, surfaced
     *                                                                     rather than re-derived, so a domain rule
     *                                                                     the host knows nothing about still reaches
     *                                                                     the user
     * @param  list<array<string, mixed>>  $normalizedRows  accepted rows as the
     *                                                      app normalized them, for the preview. The
     *                                                      host must not re-derive these between
     *                                                      validation and apply: the pipeline
     *                                                      verifies a hash over the frozen rows and
     *                                                      will reject a batch whose rows changed
     */
    public function __construct(
        public readonly string $batchId,
        public readonly ImportStage $stage,
        public readonly bool $applyEligible,
        public readonly int $rowCount = 0,
        public readonly int $acceptedCount = 0,
        public readonly int $rejectedCount = 0,
        public readonly ?int $skippedCount = null,
        public readonly array $rejectedRows = [],
        public readonly array $normalizedRows = [],
        /** @var list<ImportRowDisposition> bounded preview/detail rows */
        public readonly array $rowDispositions = [],
        /**
         * Exact totals by ImportRowDisposition status when `rowDispositions`
         * is intentionally only a bounded preview sample.
         *
         * @var array<string, int>
         */
        public readonly array $dispositionCounts = [],
        public readonly ?string $errorCode = null,
        /**
         * Where the host should attach the uploaded file.
         *
         * Present for a pipeline that requires the batch to carry the file as
         * its own scanned evidence before it will validate anything. The App
         * reports its identity and the host performs the attach: creating files
         * and attachments is a host capability, and an App cannot reach the host
         * models behind it.
         */
        public readonly ?AttachmentTarget $attachmentTarget = null,
    ) {}

    /** Whether duplicate detection is a concept this pipeline has at all. */
    public function reportsDuplicates(): bool
    {
        return $this->skippedCount !== null;
    }

    public function reportsDispositions(): bool
    {
        return $this->rowDispositions !== [] || $this->dispositionCounts !== [];
    }

    public function dispositionCount(string $status): int
    {
        if (array_key_exists($status, $this->dispositionCounts)) {
            return max(0, (int) $this->dispositionCounts[$status]);
        }

        return count(array_filter(
            $this->rowDispositions,
            static fn (ImportRowDisposition $row): bool => $row->status === $status,
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'batch_id' => $this->batchId,
            'stage' => $this->stage->value,
            'apply_eligible' => $this->applyEligible,
            'row_count' => $this->rowCount,
            'accepted_count' => $this->acceptedCount,
            'rejected_count' => $this->rejectedCount,
            'skipped_count' => $this->skippedCount,
            'reports_duplicates' => $this->reportsDuplicates(),
            'rejected_rows' => $this->rejectedRows,
            'row_dispositions' => array_map(
                static fn (ImportRowDisposition $row): array => $row->toArray(),
                $this->rowDispositions,
            ),
            'disposition_counts' => $this->dispositionCounts,
            'error_code' => $this->errorCode,
        ];
    }
}
