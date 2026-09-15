<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain;

use Nexia\Approval\Domain\Enums\ApprovalStepKind;

final readonly class ApprovalLineStepDefinition
{
    public function __construct(
        public ApprovalStepKind $kind,
        public int $userId,
        public bool $finalAuthority = false,
    ) {
        if ($this->userId <= 0) {
            throw new ApprovalException('Approval step requires a positive user id.');
        }

        if ($this->finalAuthority && $this->kind !== ApprovalStepKind::Approval) {
            throw new ApprovalException('Only an approval step can hold final authority.');
        }
    }

    public static function approver(int $userId, bool $finalAuthority = false): self
    {
        return new self(ApprovalStepKind::Approval, $userId, $finalAuthority);
    }

    public static function consultant(int $userId): self
    {
        return new self(ApprovalStepKind::Consultation, $userId);
    }

    /**
     * @return array{kind: string, user_id: int, is_final_authority: bool}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'user_id' => $this->userId,
            'is_final_authority' => $this->finalAuthority,
        ];
    }
}
