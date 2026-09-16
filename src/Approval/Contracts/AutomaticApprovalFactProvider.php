<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\AutomaticApprovalEvaluation;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/**
 * Reads the current persisted App subject while Core's bound-submission
 * transaction is active. Implementations must lock the subject row before
 * reading it, return null when it is unavailable, and derive resourceVersion,
 * resourceRef, facts, and the `CanonicalPayloadFingerprint::sha256()`
 * submitted-document snapshot fingerprint
 * from that locked current subject. Request payload values never authorize an
 * automatic decision; any mismatch fails the submission closed.
 */
interface AutomaticApprovalFactProvider
{
    public function evaluate(ResourceRef $resourceRef, LegalEntity $legalEntity): ?AutomaticApprovalEvaluation;
}
