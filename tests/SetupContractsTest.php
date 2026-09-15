<?php

declare(strict_types=1);

use Nexia\Mutation\MutationMatcher;
use Nexia\Mutation\MutationOperation;
use Nexia\Setup\Contracts\SetupTaskContribution;
use Nexia\Setup\Contracts\SetupTaskEvaluator;
use Nexia\Setup\Data\SetupBlocker;
use Nexia\Setup\Data\SetupCriterion;
use Nexia\Setup\Data\SetupEvaluationContext;
use Nexia\Setup\Data\SetupTaskAction;
use Nexia\Setup\Data\SetupTaskAssessment;
use Nexia\Setup\Data\SetupTaskDefinition;
use Nexia\Setup\Data\SetupTaskSection;
use Nexia\Setup\Enums\SetupTaskCompletionMode;
use Nexia\Setup\Enums\SetupTaskImportance;

require dirname(__DIR__).'/vendor/autoload.php';

$section = new SetupTaskSection('configuration', 'sample.setup.configuration', null, 20);
$matcher = new MutationMatcher(
    resourceKeys: ['sample.configuration'],
    operations: [MutationOperation::Created, MutationOperation::Updated],
);
$action = new SetupTaskAction(
    'sample://setup/configuration',
    ['sample.settings.update'],
    $matcher,
);
$legacyAction = new SetupTaskAction(
    'sample://setup/legacy',
    ['sample.settings.read'],
);
$assessment = new SetupTaskAssessment(
    applicable: true,
    criteria: [
        new SetupCriterion('configured', 'sample.setup.configured', true),
        new SetupCriterion('verified', 'sample.setup.verified', false, false),
    ],
    blockers: [new SetupBlocker('missing_connection', 'sample.setup.blockers.missing_connection')],
    actionOverride: $action,
);

$evaluator = new class implements SetupTaskEvaluator
{
    public function evaluate(SetupEvaluationContext $context): SetupTaskAssessment
    {
        return new SetupTaskAssessment($context->locale === 'en', [
            new SetupCriterion('configured', 'sample.setup.configured', true),
        ]);
    }
};

$definition = new SetupTaskDefinition(
    key: 'sample.configure_workspace',
    revision: 1,
    priority: 30,
    titleKey: 'sample.setup.configure_workspace',
    descriptionKey: 'sample.setup.configure_workspace_description',
    importance: SetupTaskImportance::Required,
    completionMode: SetupTaskCompletionMode::Live,
    skippable: false,
    requiresAcknowledgement: false,
    dependencies: [],
    evaluatorClass: $evaluator::class,
    defaultAction: $action,
    section: $section,
);

$contribution = new class($definition) implements SetupTaskContribution
{
    public function __construct(private SetupTaskDefinition $definition) {}

    public function tasks(): array
    {
        return [$this->definition];
    }
};

$context = new SetupEvaluationContext(
    42,
    'actor-7',
    'en',
    new DateTimeImmutable('2026-08-17T00:00:00+00:00'),
    'sample.configure_workspace',
);

if ($contribution->tasks() !== [$definition]
    || $evaluator->evaluate($context)->applicable !== true
    || ! $assessment->hasPartialEvidence()
    || ! $assessment->requiredCriteriaSatisfied()
    || $assessment->actionOverride !== $action
    || $action->recheckAfter !== $matcher
    || $legacyAction->recheckAfter !== null
    || $definition->completionMode !== SetupTaskCompletionMode::Live
    || $definition->importance !== SetupTaskImportance::Required
    || $definition->section !== $section
    || $context->taskKey !== 'sample.configure_workspace'
) {
    throw new RuntimeException('Setup contracts changed unexpectedly.');
}

fwrite(STDOUT, "Setup contracts passed.\n");
