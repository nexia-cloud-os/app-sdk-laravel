<?php

declare(strict_types=1);

use Nexia\Guidance\Contracts\FeatureGuideContribution;
use Nexia\Guidance\Data\FeatureGuideDefinition;
use Nexia\Guidance\Data\FeatureGuideStep;

require dirname(__DIR__).'/vendor/autoload.php';

$step = new FeatureGuideStep(
    key: 'open_list',
    route: '/apps/sample-owner/employment-categories',
    anchor: 'people-employment-category-list',
    titleKey: 'sample-owner.guides.employment_categories.steps.list.title',
    descriptionKey: 'sample-owner.guides.employment_categories.steps.list.description',
    placement: 'right',
    agentPromptKey: 'sample-owner.guides.employment_categories.steps.list.agent_prompt',
);
$guide = new FeatureGuideDefinition(
    key: 'sample-owner.employment_categories',
    revision: 1,
    ownerKey: 'sample-owner',
    priority: 100,
    titleKey: 'sample-owner.guides.employment_categories.title',
    descriptionKey: 'sample-owner.guides.employment_categories.description',
    icon: 'briefcase-business',
    requiredAnyPermissions: ['sample-owner.employment_category.read'],
    requiredAllPermissions: ['sample-owner.employment_category.create'],
    steps: [$step],
);
$contribution = new class($guide) implements FeatureGuideContribution
{
    public function __construct(private readonly FeatureGuideDefinition $guide) {}

    public function ownerKey(): string
    {
        return 'sample-owner';
    }

    public function guides(): array
    {
        return [$this->guide];
    }
};

if ($contribution->ownerKey() !== $guide->ownerKey
    || $contribution->guides() !== [$guide]
    || $guide->steps !== [$step]
    || $step->agentPromptKey === null) {
    throw new RuntimeException('Guidance contracts changed unexpectedly.');
}

fwrite(STDOUT, "Guidance contracts passed.\n");
