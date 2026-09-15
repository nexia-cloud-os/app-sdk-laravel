<?php

declare(strict_types=1);

use Nexia\Approval\Domain\Enums\ApprovalActionDecision;
use Nexia\Approval\Domain\Enums\ApprovalDocumentEditorMode;
use Nexia\Approval\Domain\Enums\ApprovalFormKind;
use Nexia\Approval\Domain\Enums\ApprovalRoutePolicyStatus;
use Nexia\Approval\Domain\Enums\ApprovalState;
use Nexia\Approval\Domain\Enums\ApprovalStepKind;

require dirname(__DIR__).'/vendor/autoload.php';

if (! ApprovalFormKind::Business->isBusiness()
    || ! ApprovalFormKind::Free->isFree()
    || ! ApprovalDocumentEditorMode::Optional->isOffered()
    || ApprovalDocumentEditorMode::Optional->isRequired()
    || ! ApprovalDocumentEditorMode::Required->isRequired()
    || ! ApprovalRoutePolicyStatus::Draft->isMutable()
    || ! ApprovalRoutePolicyStatus::Active->isActive()
    || ! ApprovalState::Cancelled->isTerminal()
    || ApprovalState::Submitted->isTerminal()
    || ! ApprovalStepKind::Approval->accepts(ApprovalActionDecision::Approve)
    || ApprovalStepKind::Approval->accepts(ApprovalActionDecision::Consult)
    || ! ApprovalStepKind::Consultation->accepts(ApprovalActionDecision::Object)
) {
    throw new RuntimeException('Approval domain enum contracts changed unexpectedly.');
}

fwrite(STDOUT, "Approval domain enum contracts passed.\n");
