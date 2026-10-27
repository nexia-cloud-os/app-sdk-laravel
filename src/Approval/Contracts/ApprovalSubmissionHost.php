<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\ApprovalSubmissionDelivery;
use Nexia\Approval\ApprovalSubmissionReceipt;
use Nexia\Approval\BoundApprovalSubmission;

/** Opt-in asynchronous submission; the synchronous ApprovalHost contract is unchanged. */
interface ApprovalSubmissionHost
{
    /**
     * Call while durably freezing the App-owned draft. Return the receipt to the
     * browser, without reporting approval completion. The named work handler must
     * be declared by this App and commit/release the frozen draft idempotently.
     * It receives submission_id and runs only after the originating App write commits.
     * Delivery may repeat after a lost worker reply, including after a local commit.
     * Persist the receipt outcome with the local mutation; reject conflicting outcomes.
     *
     * subjectRevision is the App record revision, distinct from the document content hash.
     * appDraftRef is the App-issued opaque reference for resuming the retained draft.
     *
     * @param list<string> $attachmentStageIds
     */
    public function enqueueBound(BoundApprovalSubmission $submission, array $attachmentStageIds, string $finalizeWorkKey, string $appDraftRef, string $subjectRevision): ApprovalSubmissionReceipt;

    /** Available only to the exact managed finalization work run for this receipt. */
    public function delivery(string $submissionId): ApprovalSubmissionDelivery;
}
