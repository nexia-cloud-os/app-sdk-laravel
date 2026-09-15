<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\ResourceImport\ImportBatchState;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/**
 * The four verbs an app's import pipeline already has, as a contract.
 *
 * An adapter is expected to be thin — the pipeline behind it keeps its own
 * transaction boundaries, locking, mapping-hash verification, and domain rules,
 * and the adapter's job is translation, not policy. In particular an adapter must
 * not swallow a domain rejection to make an import look cleaner: a row the app
 * refuses has to arrive in `rejectedRows` so the user sees the app's judgement.
 */
interface ResourceImportPipeline
{
    /**
     * Open (or re-find) a batch for this file and mapping.
     *
     * Idempotent on `$idempotencyKey`: a repeat with the same file hash, mapping,
     * and profile returns the existing batch rather than a second one. A repeat
     * with the same key and different content is a conflict, not an update.
     *
     * `$profileKey` identifies the *format*, not the vendor — the same template
     * from any sender is the same profile — which is what lets an approved
     * mapping be reused later without recording who sent the file.
     *
     * `$rowCount` and `$fileId` are here because at least one pipeline needs
     * them at this stage rather than at validation: it records the expected row
     * count on the batch and refuses a later `validateRows()` whose count
     * disagrees, and it requires the batch to carry the uploaded file as
     * attachment evidence with a matching checksum before it will validate
     * anything. Passing the count only with the rows would make that check
     * unimplementable.
     *
     * @param  string  $fileId  host File identifier, so the pipeline can attach
     *                          the upload as its own evidence
     * @param  int  $rowCount  data rows the host will submit to `validateRows()`
     * @param  array<string, mixed>  $mapping  the approved mapping spec
     */
    public function receive(
        LegalEntity $legalEntity,
        Actor $actor,
        string $profileKey,
        int $schemaVersion,
        string $fileId,
        string $fileHash,
        int $rowCount,
        array $mapping,
        string $idempotencyKey,
    ): ImportBatchState;

    /**
     * Validate rows against the app's own rules and freeze what passed.
     *
     * The rows are the host's mapped output; everything the app decides about
     * them — types, references, domain invariants — happens here and is reported
     * back rather than pre-empted by the caller.
     *
     * `$mapping` is re-supplied rather than read back from the batch because at
     * least one pipeline records only the mapping's hash at receive and verifies
     * the caller still holds the same mapping here. Passing it is what proves the
     * rows were produced by the mapping the batch was opened with.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $mapping  the same mapping passed to `receive()`
     */
    public function validateRows(
        string $batchId,
        LegalEntity $legalEntity,
        Actor $actor,
        array $rows,
        array $mapping,
    ): ImportBatchState;

    /** Commit the frozen rows. Only legal when the batch is apply-eligible. */
    public function apply(string $batchId, LegalEntity $legalEntity, Actor $actor): ImportBatchState;

    /**
     * Re-open a failed or rejected batch under a new idempotency key.
     *
     * @param  array<string, mixed>  $mapping
     */
    public function retry(
        string $batchId,
        LegalEntity $legalEntity,
        Actor $actor,
        array $mapping,
        string $idempotencyKey,
    ): ImportBatchState;
}
