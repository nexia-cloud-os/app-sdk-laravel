<?php

declare(strict_types=1);

namespace Nexia\Approval;

use Nexia\ResourceReference\ResourceRef;

/** Immutable facts read by an owning App from its persisted approval subject. */
final readonly class AutomaticApprovalEvaluation
{
    /** @param array<string, bool|int|string> $facts */
    public function __construct(
        public ResourceRef $resourceRef,
        public string $resourceVersion,
        public string $documentFingerprint,
        public array $facts,
    ) {}
}
