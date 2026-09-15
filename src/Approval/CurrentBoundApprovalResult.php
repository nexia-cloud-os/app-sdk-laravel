<?php

declare(strict_types=1);

namespace Nexia\Approval;

/** Exact runtime references snapshotted for a current bound submission. */
final readonly class CurrentBoundApprovalResult
{
    public function __construct(
        public ApprovalCaseReference $case,
        public ApprovalRouteReference $route,
        public string $templateKey,
        public int $templateVersion,
        public string $bindingKey,
        public string $bindingVersion,
    ) {}
}
