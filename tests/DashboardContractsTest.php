<?php

declare(strict_types=1);

use Nexia\Dashboard\DashboardQuerySource;
use Nexia\Dashboard\DashboardWidget;
use Nexia\Dashboard\DashboardWidgetKind;
use Nexia\Dashboard\DashboardWidgetParameter;
use Nexia\Dashboard\RendererCapability;
use Nexia\Permission\SubjectPopulation;

require dirname(__DIR__).'/vendor/autoload.php';

$parameter = new DashboardWidgetParameter(
    name: 'status',
    type: DashboardWidgetParameter::TYPE_STRING,
    description: 'Status filter',
    enum: ['open', 'closed'],
    default: 'open',
    target: 'filter.status',
);

$widget = new DashboardWidget(
    key: 'sample.open_reports',
    titleKey: 'dashboard-widgets.sample.report.open.title',
    description: 'Shows open expense reports.',
    spec: ['component' => 'Table', 'props' => ['titleKey' => 'dashboard-widgets.sample.report.open.title']],
    kind: DashboardWidgetKind::Table,
    parameters: [$parameter],
    reportingViewKey: 'sample.reports',
    familyKey: 'sample',
    capability: RendererCapability::HumanAndAgent,
    requiredAnyPermission: ['sample.report.read'],
);

$row = $widget->toArray();

if ($row['kind'] !== 'table'
    || $row['title_key'] !== 'dashboard-widgets.sample.report.open.title'
    || $row['spec']['component'] !== 'Table'
    || $row['parameters'][0]['target'] !== 'filter.status'
    || ! $widget->capability->allowsAgent()
    || ! $widget->capability->allowsHumanDashboard()
    || RendererCapability::AgentOnly->allowsHumanDashboard()
    || RendererCapability::HumanOnly->allowsAgent()
) {
    throw new RuntimeException('Dashboard widget contracts changed unexpectedly.');
}

$querySource = new DashboardQuerySource(
    key: 'sample-time.personal_time_leave.read',
    description: 'Read the current user personal leave balance.',
    path: '/api/legal-entities/{legalEntity:public_id}/sample-time/personal-leave',
    permission: 'sample-time.leave_balance.read',
    subjectPopulation: SubjectPopulation::Self,
    pollingOnly: true,
    capability: RendererCapability::HumanAndAgent,
);
$manifestRow = $querySource->agentManifestRow('sample-time');
$definitionRow = $querySource->agentDefinitionRow('sample-time');
$backingSourceRow = $querySource->backingSourceRow('sample-time');

if ($manifestRow !== [
    'key' => 'sample-time.personal_time_leave.read',
    'tier' => 'auto',
    'description' => 'Read the current user personal leave balance.',
    'executor' => 'laravel',
    'routing' => [
        'lanes' => ['dashboard'],
        'effect' => 'read',
        'surface' => 'dashboard',
        'preconditions' => [],
    ],
    'http' => [
        'method' => 'POST',
        'path' => '/api/agent/dashboard-query-sources/sample-time.personal_time_leave.read/invoke',
    ],
    'permissions' => ['sample-time.leave_balance.read'],
    'input_schema' => null,
    'app_key' => 'sample-time',
] || [
    'method' => $definitionRow['method'],
    'path' => $definitionRow['path'],
] !== $manifestRow['http']
    || $definitionRow['executor'] !== 'laravel'
    || $definitionRow['routing'] !== $manifestRow['routing']
    || $definitionRow['agent_visible'] !== true
    || $backingSourceRow['method'] !== 'GET'
    || $backingSourceRow['path'] !== '/api/legal-entities/{legalEntity:public_id}/sample-time/personal-leave'
    || $backingSourceRow['routing'] !== $manifestRow['routing']
    || $backingSourceRow['app_key'] !== 'sample-time'
) {
    throw new RuntimeException('Dashboard query source Agent contract changed unexpectedly.');
}

$invalidFactories = [
    static fn (): DashboardWidgetParameter => new DashboardWidgetParameter('', 'string', 'invalid'),
    static fn (): DashboardWidgetParameter => new DashboardWidgetParameter('status', 'object', 'invalid'),
    static fn (): DashboardWidgetParameter => new DashboardWidgetParameter('status', 'string', 'invalid', []),
    static fn (): DashboardWidgetParameter => new DashboardWidgetParameter('status', 'string', 'invalid', target: '../status'),
    static fn (): DashboardWidget => new DashboardWidget('empty', 'widgets.empty.title', 'Invalid', [], DashboardWidgetKind::Metric),
    static fn (): DashboardWidget => new DashboardWidget('bad', 'widgets.bad.title', 'Invalid', ['props' => []], DashboardWidgetKind::Metric),
    static fn (): DashboardWidget => new DashboardWidget('blank-key', ' ', 'Invalid', ['component' => 'Table'], DashboardWidgetKind::Metric),
    static fn (): DashboardWidget => new DashboardWidget('duplicate', 'widgets.duplicate.title', 'Invalid', ['component' => 'Table'], DashboardWidgetKind::Table, [$parameter, $parameter]),
    static fn (): DashboardQuerySource => new DashboardQuerySource(
        key: 'sample-time.personal-time-leave.read',
        description: 'Invalid key.',
        path: '/api/sample-time/personal-leave',
        permission: 'sample-time.leave_balance.read',
        subjectPopulation: SubjectPopulation::Self,
        pollingOnly: true,
    ),
];

foreach ($invalidFactories as $factory) {
    try {
        $factory();
    } catch (InvalidArgumentException) {
        continue;
    }

    throw new RuntimeException('Invalid dashboard contribution was accepted.');
}

fwrite(STDOUT, "Dashboard contracts passed.\n");
