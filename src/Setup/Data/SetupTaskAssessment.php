<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

/**
 * Framework-neutral evaluator output, independent of host authorization,
 * persistence, task status, and interaction state.
 */
final readonly class SetupTaskAssessment
{
    /**
     * @param list<SetupCriterion> $criteria
     * @param list<SetupBlocker> $blockers
     */
    public function __construct(
        public bool $applicable,
        public array $criteria = [],
        public array $blockers = [],
        public ?SetupTaskAction $actionOverride = null,
    ) {
    }

    public function hasPartialEvidence(): bool
    {
        foreach ($this->criteria as $criterion) {
            if ($criterion->satisfied) {
                return true;
            }
        }

        return false;
    }

    public function requiredCriteriaSatisfied(): bool
    {
        foreach ($this->criteria as $criterion) {
            if ($criterion->required && ! $criterion->satisfied) {
                return false;
            }
        }

        return true;
    }
}
