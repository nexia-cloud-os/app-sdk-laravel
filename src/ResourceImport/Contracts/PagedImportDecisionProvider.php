<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/**
 * Optional bounded review and settlement for an import's frozen decisions.
 * A pipeline may expose this through a lazy pagedDecisionProvider($batchId)
 * method that returns null for a batch without a paged review snapshot;
 * keep that provider outside contribution discovery when older SDKs must load
 * the pipeline without loading this newer interface.
 */
interface PagedImportDecisionProvider
{
    /**
     * Return the current immutable analysis generation and selection revision.
     * The generation identifies the batch, source and mapping, not just the cursor.
     * Plan summary fields must be bounded and exclude the paged decisions.
     *
     * Paged reviews support choice and multiple_choice decisions only. Legacy
     * row_acceptance keeps the existing complete-plan auto-exclusion path.
     *
     * @return array{generation:string,revision:int,total:int,blocking:int,complete:bool,decision_types:list<string>,plan:array<string,mixed>}
     */
    public function decisionSummary(string $batchId, LegalEntity $legalEntity, Actor $actor): array;

    /**
     * Page decisions, one decision's options, plan sections, one section's items,
     * or reference issues. Supply a collection key for options or section items.
     * The limit is between 1 and 100 and cursors are at most 2048 bytes.
     * Pages retain stable order and
     * exact totals for the frozen generation; reading never implies consent.
     * Bind cursors to batch, generation, collection and key, and to the selection
     * revision when staged choices can change the collection.
     *
     * @param 'decisions'|'options'|'sections'|'section_items'|'reference_issues' $collection
     * @return array{items:list<array<string,mixed>>,next_cursor:?string,total:int}
     */
    public function reviewPage(string $batchId, LegalEntity $legalEntity, Actor $actor,
        string $generation, string $collection, ?string $collectionKey, ?string $cursor, int $limit): array;

    /**
     * Atomically apply a bounded change against an expected revision. Repeating
     * the same operation ID and payload returns the original receipt; reuse with
     * different input fails. Inspect an existing operation receipt before
     * testing its original expected revision, so an exact retry observes the
     * original result. A definitively stale revision must fail validation.
     * A change names one decision and an action: set,
     * clear, select, unselect, select_all, clear_all, or accept_notice. Set
     * carries one value; select/unselect carry at most 100. Individual values
     * must belong to the offered generation. Select-all applies only
     * to a multiple choice after an explicit user action. A notice requires a
     * single explicit confirmation of its complete count and frozen fingerprint;
     * neither fetching a page nor selecting all implies notice consent.
     *
     * @param array<string,mixed> $change
     * @return array{revision:int,complete:bool}
     */
    public function stageDecision(string $batchId, LegalEntity $legalEntity, Actor $actor,
        string $generation, int $revision, string $operationId, array $change): array;

    /**
     * Observe the immutable App receipt for an operation without re-executing it.
     * Null means no committed receipt is visible, not that execution never started.
     *
     * @return array{revision:int,complete:bool}|array{submission:string}|null
     */
    public function decisionOperation(string $batchId, LegalEntity $legalEntity, Actor $actor,
        string $generation, string $operationId): ?array;

    /**
     * Seal a complete revision as an opaque, actor- and batch-bound submission.
     * An incomplete, changed or stale generation must be rejected. Repeating
     * the same operation ID and payload returns the same submission; reuse
     * with a different payload must fail.
     */
    public function sealDecisions(string $batchId, LegalEntity $legalEntity, Actor $actor,
        string $generation, int $revision, string $operationId): string;
}
