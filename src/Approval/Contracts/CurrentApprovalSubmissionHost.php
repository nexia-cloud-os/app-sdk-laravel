<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\ApprovalRouteReference;
use Nexia\Approval\ApprovalSubmissionReceipt;
use Nexia\Approval\CurrentBoundApprovalSubmission;

/** Optional host support for resolving the current approval after the App write commits. */
interface CurrentApprovalSubmissionHost
{
    /** True only for an admitted request with the specified registered finalizer. */
    public function availableFor(string $finalizeWorkKey): bool;

    /**
     * Freeze the App draft and record the intent in the same transaction.
     * The finalizer receives the resolved current result through ApprovalSubmissionHost::delivery().
     *
     * @param list<string> $attachmentStageIds
     */
    public function enqueueCurrentBound(
        CurrentBoundApprovalSubmission $submission,
        array $attachmentStageIds,
        string $finalizeWorkKey,
        string $appDraftRef,
        string $subjectRevision,
        ?int $expectedTemplateVersion = null,
        ?ApprovalRouteReference $expectedRoute = null,
    ): ApprovalSubmissionReceipt;
}
