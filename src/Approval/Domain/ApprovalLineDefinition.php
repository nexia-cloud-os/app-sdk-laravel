<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain;

use Nexia\Approval\Domain\Enums\ApprovalStepKind;

final readonly class ApprovalLineDefinition
{
    /**
     * @param  list<list<ApprovalLineStepDefinition>>  $stages
     */
    public function __construct(private array $stages)
    {
        if ($this->stages === []) {
            throw new ApprovalException('Approval line requires at least one stage.');
        }

        $seenUserIds = [];
        $hasApprovalStep = false;

        foreach ($this->stages as $stage) {
            if ($stage === []) {
                throw new ApprovalException('Approval line stage cannot be empty.');
            }

            foreach ($stage as $step) {
                if (! $step instanceof ApprovalLineStepDefinition) {
                    throw new ApprovalException('Approval line stages must contain step definitions.');
                }

                if (in_array($step->userId, $seenUserIds, true)) {
                    throw new ApprovalException('Approval line cannot assign the same user to multiple steps.');
                }

                $hasApprovalStep = $hasApprovalStep || $step->kind === ApprovalStepKind::Approval;
                $seenUserIds[] = $step->userId;
            }
        }

        if (! $hasApprovalStep) {
            throw new ApprovalException('Approval line requires at least one approval step.');
        }
    }

    /**
     * @param  list<list<ApprovalLineStepDefinition>>  $stages
     */
    public static function fromStages(array $stages): self
    {
        return new self($stages);
    }

    /**
     * @return list<list<ApprovalLineStepDefinition>>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    /**
     * @return list<array{position: int, steps: list<array{kind: string, user_id: int, is_final_authority: bool}>}>
     */
    public function toSnapshot(): array
    {
        $snapshot = [];

        foreach ($this->stages as $index => $stage) {
            $snapshot[] = [
                'position' => $index + 1,
                'steps' => array_map(
                    static fn (ApprovalLineStepDefinition $step): array => $step->toArray(),
                    $stage,
                ),
            ];
        }

        return $snapshot;
    }
}
