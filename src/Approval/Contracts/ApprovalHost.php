<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\ApprovalCaseReference;
use Nexia\Approval\ApprovalCaseSummary;
use Nexia\Approval\ApprovalDocumentPreview;
use Nexia\Approval\ApprovalRouteReference;
use Nexia\Approval\ApprovalAttachmentEvidence;
use Nexia\Approval\BoundApprovalSubmission;
use Nexia\Approval\CurrentBoundApprovalResult;
use Nexia\Approval\CurrentBoundApprovalSubmission;
use Nexia\Approval\Domain\ApprovalLineDefinition;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

interface ApprovalHost
{
    /** Resolve the one active route visible to the current tenant and optional Legal Entity applicability. */
    public function resolveRoute(string $routePolicyKey, ?LegalEntity $legalEntity): ?ApprovalRouteReference;

    public function caseSummary(string $approvalCasePublicId): ?ApprovalCaseSummary;

    public function routeExists(string $routePolicyKey, ?int $legalEntityId): bool;

    /** @param array<string, mixed> $values */
    public function renderDocument(string $resourceKey, array $values): ?ApprovalDocumentPreview;

    /**
     * @param list<string> $stagePublicIds
     * @return list<ApprovalAttachmentEvidence>
     */
    public function prepareSupportingAttachments(
        LegalEntity $legalEntity,
        Actor $drafter,
        array $stagePublicIds,
    ): array;

    /** @param array<string, mixed> $payload */
    public function resolveRouteLine(
        string $routePolicyPublicId,
        string $routePolicyKey,
        int $routePolicyVersion,
        LegalEntity $legalEntity,
        Actor $submitter,
        ResourceRef $resourceRef,
        array $payload,
    ): ?ApprovalLineDefinition;

    /** @param list<ApprovalAttachmentEvidence> $attachments */
    public function submitBound(
        BoundApprovalSubmission $submission,
        array $attachments,
    ): ApprovalCaseReference;

    /**
     * Resolve current published/active versions and snapshot them atomically.
     *
     * @param list<ApprovalAttachmentEvidence> $attachments
     */
    public function submitCurrentBound(
        CurrentBoundApprovalSubmission $submission,
        array $attachments,
    ): CurrentBoundApprovalResult;

    public function recall(string $approvalCasePublicId, Actor $actor): void;
}
