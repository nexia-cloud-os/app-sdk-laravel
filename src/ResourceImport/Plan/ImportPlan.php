<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Plan;

use Nexia\ResourceImport\Decision\ImportDecision;

/**
 * What a batch will bring into being, and what a person still has to settle.
 *
 * Counts alone are the whole story for a bank statement — "312 added, 4 already
 * present" answers everything before approving one. A personnel file is not
 * like that: approving it creates two companies, twenty-one departments and
 * three hundred employments, and may invite a selected subset to log in;
 * "309 rows" says none of it. So a pipeline with structure to declare declares
 * it, and the card states it before the button is offered.
 *
 * Typed rather than a free-form detail bag. A bag would invite every App to put
 * anything in it and leave the host with no basis on which to render — or
 * refuse — what arrived, which is the reason the first version of this
 * capability was kept in the host and the reason the pipeline that needed it
 * had to live there too.
 *
 * Nothing here names a domain. The host draws sections, lines and decisions; it
 * does not know that one of the numbers is departments or that one of the
 * decisions is a grade ladder. That is what lets the same renderer serve an App
 * the host has never heard of.
 */
final readonly class ImportPlan
{
    /**
     * @param  list<ImportPlanSection>  $sections  what will be created or matched
     * @param  list<ImportDecision>  $decisions  what a person still has to settle
     * @param  list<string>  $blockingIssueKeys  i18n keys for things that hold the
     *                                           approval and are *not* decisions — a value appearing in two
     *                                           presentations, where the user cannot answer inline and has to go
     *                                           back to the file. Separate from decisions because the card offers
     *                                           no input for these, only an explanation
     * @param  array<string, string|int>  $blockingIssueParams  keyed by the issue
     *                                                          key they belong to
     * @param  list<array{issue: array<string, mixed>, count: int}>  $referenceIssues
     *                                                                                 distinct unresolved references across the complete batch
     */
    public function __construct(
        public array $sections = [],
        public array $decisions = [],
        public array $blockingIssueKeys = [],
        public array $blockingIssueParams = [],
        public array $referenceIssues = [],
    ) {}

    /**
     * Whether an approval may be offered at all.
     *
     * Derived rather than declared, so a plan cannot say it is ready while
     * carrying a blocking decision. What "settled" means for a decision is the
     * host's to check against the reply it collected — this answers the question
     * before any reply exists.
     */
    public function hasBlockingWork(): bool
    {
        if ($this->blockingIssueKeys !== []) {
            return true;
        }

        foreach ($this->decisions as $decision) {
            if ($decision->blocking) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sections' => array_map(
                static fn (ImportPlanSection $section): array => $section->toArray(),
                $this->sections,
            ),
            'decisions' => array_map(
                static fn (ImportDecision $decision): array => $decision->toArray(),
                $this->decisions,
            ),
            'blocking_issue_keys' => $this->blockingIssueKeys,
            'blocking_issue_params' => $this->blockingIssueParams,
            'reference_issues' => $this->referenceIssues,
        ];
    }
}
