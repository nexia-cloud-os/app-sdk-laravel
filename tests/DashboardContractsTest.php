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
$statusSource = new DashboardQuerySource('sample-time.personal_status.read', 'Status source', '/api/sample-time/status',
    'sample-time.leave_balance.read', SubjectPopulation::Self, pollingOnly: true, widgetAppStatuses: ['people']);
if ($statusSource->widgetAppStatuses !== ['people']) throw new RuntimeException('Widget status declaration was not retained.');
$selectedScope = new DashboardQuerySource('sample-time.selected.read', 'Selected source', '/api/sample-time/selected',
    'sample-time.leave_balance.read', SubjectPopulation::Self, pollingOnly: true, usesLegalEntitySelection: true, legalEntityScope: 'selected');
$independentScope = new DashboardQuerySource('sample-time.independent.read', 'Independent source', '/api/sample-time/independent',
    'sample-time.leave_balance.read', SubjectPopulation::Self, pollingOnly: true, legalEntityScope: 'independent');
if ($querySource->legalEntityScope !== null || $selectedScope->legalEntityScope !== 'selected' || $independentScope->legalEntityScope !== 'independent') {
    throw new RuntimeException('Dashboard scope classification compatibility failed.');
}

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

$reference = ['resource_key' => 'people.worker_assignment', 'purpose' => 'sample-time.manager', 'visibility_group' => 'manager', 'population' => 'direct_reports', 'include_party_key' => true];
$referenceSource = new DashboardQuerySource('sample-time.summary.read', 'Manager reference', '/api/sample-time/summary',
    'sample-time.leave_balance.read', SubjectPopulation::LegalEntity, pollingOnly: true, usesLegalEntitySelection: true,
    visibilityPermissionGroups: ['manager' => ['sample-time.leave_balance.read']], widgetReferenceReads: [$reference]);
if ($referenceSource->widgetReferenceReads !== [$reference]) throw new RuntimeException('Widget reference population was not retained.');

$tenantWide = new DashboardQuerySource('sample-time.company.read', 'Company source', '/api/sample-time/company',
    'sample-time.leave_balance.read', SubjectPopulation::LegalEntity, pollingOnly: true,
    usesLegalEntitySelection: true, legalEntityScope: 'selected', tenantWideRead: true);
if (! $tenantWide->tenantWideRead) throw new RuntimeException('Tenant-wide dashboard contract was not retained.');

$invalidFactories = [
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.company.read', 'Invalid company source', '/api/sample-time/company',
        'sample-time.leave_balance.read', SubjectPopulation::LegalEntity, pollingOnly: true, tenantWideRead: true),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.company.read', 'Invalid agent source', '/api/sample-time/company',
        'sample-time.leave_balance.read', SubjectPopulation::LegalEntity, pollingOnly: true, usesLegalEntitySelection: true,
        legalEntityScope: 'selected', capability: RendererCapability::HumanAndAgent, tenantWideRead: true),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.company.read', 'Invalid self source', '/api/sample-time/company',
        'sample-time.leave_balance.read', SubjectPopulation::Self, pollingOnly: true, usesLegalEntitySelection: true,
        legalEntityScope: 'selected', tenantWideRead: true),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.invalid.read', 'Invalid scope', '/api/sample-time/invalid',
        'sample-time.leave_balance.read', SubjectPopulation::Self, legalEntityScope: 'selected'),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.invalid.read', 'Invalid scope', '/api/sample-time/invalid',
        'sample-time.leave_balance.read', SubjectPopulation::Self, usesLegalEntitySelection: true, legalEntityScope: 'independent'),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.invalid.read', 'Invalid scope', '/api/sample-time/invalid',
        'sample-time.leave_balance.read', SubjectPopulation::Self, legalEntityScope: 'unknown'),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Invalid party key declaration',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::LegalEntity, pollingOnly: true,
        usesLegalEntitySelection: true, visibilityPermissionGroups: ['manager' => ['sample-time.leave_balance.read']],
        widgetReferenceReads: [[...$reference, 'include_party_key' => false]]),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Invalid reference population',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::LegalEntity, pollingOnly: true,
        usesLegalEntitySelection: true, visibilityPermissionGroups: ['manager' => ['sample-time.leave_balance.read']],
        widgetReferenceReads: [[...$reference, 'population' => 'legal_entity']]),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Invalid self reference population',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::Self, pollingOnly: true,
        usesLegalEntitySelection: true, visibilityPermissionGroups: ['manager' => ['sample-time.leave_balance.read']], widgetReferenceReads: [$reference]),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Invalid status key.',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::Self,
        pollingOnly: true, widgetAppStatuses: ['People']),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Duplicate status key.',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::Self,
        pollingOnly: true, widgetAppStatuses: ['people', 'people']),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Invalid widget path.',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::Self,
        pollingOnly: true, widgetPath: '/api/sample-time/widget'),
    static fn (): DashboardQuerySource => new DashboardQuerySource('sample-time.summary.read', 'Invalid widget path.',
        '/api/sample-time/summary', 'sample-time.leave_balance.read', SubjectPopulation::Self,
        pollingOnly: true, usesLegalEntitySelection: true, widgetPath: '/api/sample-time/{legalEntity}'),
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
