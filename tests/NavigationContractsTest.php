<?php

declare(strict_types=1);

use Nexia\Navigation\Concerns\ContributesNavigationDestinationActions;
use Nexia\Navigation\Contracts\NavigationContribution;
use Nexia\Navigation\NavigationItem;
use Nexia\Navigation\ShellNavigationLocateAction;

require dirname(__DIR__).'/vendor/autoload.php';

$contributor = new class implements NavigationContribution
{
    use ContributesNavigationDestinationActions;

    public static function navigationItems(): array
    {
        return [NavigationItem::make(
            id: 'sample',
            route: '/apps/sample',
            icon: 'box',
            sort: 10,
            labelKey: 'sample-navigation.sample.label',
            appKey: 'sample',
            contextId: 'app',
            groupId: null,
        )];
    }

    public static function agentNavigationActionDefinitions(): array
    {
        return [
            ['action' => 'screen.navigate', 'route' => '/apps/sample'],
            ['route' => '/apps/ignored'],
            'ignored',
        ];
    }
};

if ($contributor::navigationItems()[0]['app_key'] !== 'sample'
    || $contributor::navigationItems()[0]['label_key'] !== 'sample-navigation.sample.label'
    || $contributor::agentNavigationActions() !== [[
        'action' => 'screen.navigate',
        'route' => '/apps/sample',
    ]]
) {
    throw new RuntimeException('Navigation contribution contracts changed unexpectedly.');
}

$locate = ShellNavigationLocateAction::make(
    action: 'sample.report.locate',
    url: '/apps/sample/reports',
    permission: ['sample.report.read'],
    entryKey: 'expense-report',
);

if ($locate['intent'] !== 'locate'
    || $locate['effect'] !== 'read_only'
    || $locate['requires_user_submit'] !== false
    || $locate['permission'] !== ['sample.report.read']
    || $locate['label_key'] !== 'agent-navigation.expense-report.label'
    || $locate['description_key'] !== 'agent-navigation.expense-report.description'
    || $locate['input_schema']['additionalProperties'] !== false
    || array_keys($locate['input_schema']['properties']) !== ['q', 'filters', 'sort']
) {
    throw new RuntimeException('Shell navigation locate action contract changed unexpectedly.');
}

fwrite(STDOUT, "Navigation contracts passed.\n");
