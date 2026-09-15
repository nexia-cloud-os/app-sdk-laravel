<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Enums;

enum ApprovalStepKind: string
{
    case Approval = 'approval';
    case Consultation = 'consultation';

    public function accepts(ApprovalActionDecision $decision): bool
    {
        return match ($this) {
            self::Approval => in_array($decision, [
                ApprovalActionDecision::Approve,
                ApprovalActionDecision::Reject,
            ], true),
            self::Consultation => in_array($decision, [
                ApprovalActionDecision::Consult,
                ApprovalActionDecision::Object,
            ], true),
        };
    }
}
