<?php

declare(strict_types=1);

use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;
use Nexia\AppDescriptors\AppDescriptorSet;
use Nexia\AppDescriptors\DecisionResultFieldDescriptor;
use Nexia\AppDescriptors\DecisionResultTemplateDescriptor;
use Nexia\AppDescriptors\DescriptorStatus;
use Nexia\AppDescriptors\ProcessStartBindingDescriptor;
use Nexia\AppDescriptors\ProcessTemplateDescriptor;
use Nexia\AppDescriptors\ProcessUserTaskFormDescriptor;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\AppDescriptors\ReportingViewDescriptor;

require dirname(__DIR__).'/vendor/autoload.php';

$field = new DecisionResultFieldDescriptor(
    id: 'decision',
    labelKey: 'sample.decision.label',
    type: 'string',
    purpose: 'plain',
);
$decision = new DecisionResultTemplateDescriptor(
    key: 'sample.approval-routing',
    version: '1.0',
    labelKey: 'sample.approval-routing.label',
    fields: [$field],
    descriptionKey: 'sample.approval-routing.description',
    decisionTable: [
        'inputs' => [['id' => 'amount', 'label' => 'Amount', 'type' => 'number']],
        'outputs' => [['id' => 'decision', 'label' => 'Decision', 'type' => 'string']],
        'rules' => [['when' => ['amount' => '*'], 'then' => ['decision' => 'review']]],
    ],
    hitPolicy: 'FIRST',
);
$process = new ProcessTemplateDescriptor(
    key: 'sample.expense-report',
    version: '1.0',
    appKey: 'sample',
    labelKey: 'sample.process.expense-report',
    category: ProcessTemplateDescriptor::CATEGORY_APP,
    structure: ['definitions' => []],
);
$coreProcess = new ProcessTemplateDescriptor(
    key: 'core.app_neutral',
    version: '1.0',
    appKey: null,
    labelKey: 'process.templates.app_neutral',
    category: ProcessTemplateDescriptor::CATEGORY_CORE,
    structure: ['definitions' => []],
);
$startBinding = new ProcessStartBindingDescriptor(
    appKey: 'sample',
    bindingKey: 'expense-report',
    definitionKey: 'sample.expense-report',
    processId: 'expense_report_process',
    resourceKey: 'sample.expense_report',
    startPermissionKey: 'sample.expense_report.submit',
    labelKey: 'sample.process.expense-report',
);
$form = new ProcessUserTaskFormDescriptor(
    key: 'sample.expense-report-review',
    appKey: 'sample',
    labelKey: 'sample.form.expense-report-review',
    outputSchema: [
        'expense_ref' => ['type' => 'resourceRef'],
        'decision' => [
            'type' => 'string',
            'required' => true,
            'enum' => ['approved', 'rejected'],
            'label_key' => 'sample.form.output.decision',
            'enum_labels' => [
                'approved' => 'sample.form.output.decision.approved',
                'rejected' => 'sample.form.output.decision.rejected',
            ],
        ],
    ],
);
$action = new ProcessWorkActionDescriptor(
    kind: 'serviceTask',
    appKey: 'sample',
    actionKey: 'expense-report.finalize',
    labelKey: 'sample.action.expense-report-finalize',
    topic: 'sample.expense-report.finalize',
);
$report = new ReportingViewDescriptor(
    key: 'sample.expense-report-summary',
    version: '1.0',
    viewName: 'expense_report_summary',
    columns: ['total'],
);

$contribution = new class implements AppDescriptorContribution
{
    public static AppDescriptorSet $descriptors;

    public static function appDescriptors(): AppDescriptorSet
    {
        return self::$descriptors;
    }
};
$contribution::$descriptors = AppDescriptorSet::of(
    $process,
    $startBinding,
    $form,
    $action,
    $decision,
    $report,
);

if ($contribution::appDescriptors()->ofType(ProcessTemplateDescriptor::class) !== [$process]
    || $contribution::appDescriptors()->ofType(ProcessStartBindingDescriptor::class) !== [$startBinding]
    || $contribution::appDescriptors()->ofType(ProcessUserTaskFormDescriptor::class) !== [$form]
    || $contribution::appDescriptors()->ofType(ProcessWorkActionDescriptor::class) !== [$action]
    || $contribution::appDescriptors()->ofType(DecisionResultTemplateDescriptor::class) !== [$decision]
    || $contribution::appDescriptors()->ofType(ReportingViewDescriptor::class) !== [$report]
) {
    throw new RuntimeException('Process descriptor contribution type views are invalid.');
}

if ($decision->status !== DescriptorStatus::Active
    || $process->status !== DescriptorStatus::Active
    || $form->status !== DescriptorStatus::Active
    || $action->status !== DescriptorStatus::Active
    || $report->status !== DescriptorStatus::Active
    || $startBinding->status !== DescriptorStatus::Active
    || $coreProcess->appKey !== null
) {
    throw new RuntimeException('Process descriptor lifecycle defaults changed unexpectedly.');
}

fwrite(STDOUT, "Process descriptor contracts are valid.\n");

$anchoredProcess = new ProcessTemplateDescriptor(
    key: 'sample.anchored',
    version: '1.0',
    appKey: 'sample',
    labelKey: 'sample.process.anchored',
    category: ProcessTemplateDescriptor::CATEGORY_APP,
    structure: ['definitions' => []],
    dependencies: [['kind' => 'resource', 'key' => 'sample.expense_report']],
    startResourceKey: 'sample.expense_report',
);
if ($anchoredProcess->startResourceKey !== 'sample.expense_report' || $process->startResourceKey !== null) {
    throw new RuntimeException('Process start resource contract was not preserved.');
}

$invalidCases = [
    ...array_map(static fn (string $resourceKey): Closure => static fn () => new ProcessTemplateDescriptor(
        key: 'sample.invalid-start-resource',
        version: '1.0',
        appKey: 'sample',
        labelKey: 'sample.process.invalid_start_resource',
        category: ProcessTemplateDescriptor::CATEGORY_APP,
        structure: ['definitions' => []],
        dependencies: [['kind' => 'resource', 'key' => 'sample.expense_report']],
        startResourceKey: $resourceKey,
    ), ['', 'sample.other_record']),
    static fn () => new DecisionResultFieldDescriptor('', 'sample.empty', 'string', 'plain'),
    static fn () => new DecisionResultTemplateDescriptor(
        key: 'sample.invalid-policy',
        version: '1.0',
        labelKey: 'sample.invalid-policy.label',
        fields: [$field],
        decisionTable: [
            'inputs' => [['id' => 'amount']],
            'outputs' => [['id' => 'decision']],
            'rules' => [['when' => ['amount' => '*'], 'then' => ['decision' => 'review']]],
        ],
        hitPolicy: 'UNKNOWN',
    ),
    // The guided editor seeds a single input column, so a multi-input starter
    // is parked and silently degrades to output columns only. Fail here, where
    // the App can still see which descriptor is wrong.
    static fn () => new DecisionResultTemplateDescriptor(
        key: 'sample.two-inputs',
        version: '1.0',
        labelKey: 'sample.two-inputs.label',
        fields: [$field],
        decisionTable: [
            'inputs' => [['id' => 'amount'], ['id' => 'region']],
            'outputs' => [['id' => 'decision']],
            'rules' => [['when' => ['amount' => '*'], 'then' => ['decision' => 'review']]],
        ],
        hitPolicy: 'FIRST',
    ),
    static fn () => new DecisionResultTemplateDescriptor(
        key: 'sample.mismatched-output',
        version: '1.0',
        labelKey: 'sample.mismatched-output.label',
        fields: [$field],
        decisionTable: [
            'inputs' => [['id' => 'amount']],
            'outputs' => [['id' => 'other']],
            'rules' => [['when' => ['amount' => '*'], 'then' => ['other' => 'review']]],
        ],
        hitPolicy: 'FIRST',
    ),
    static fn () => new DecisionResultTemplateDescriptor(
        key: 'sample.incomplete-event-input',
        version: '1.0',
        labelKey: 'sample.incomplete-event-input.label',
        fields: [$field],
        decisionTable: [
            'inputs' => [[
                'id' => 'amount',
                'expression' => 'event_payload.amount',
                'source' => ['kind' => 'event_payload', 'payload_key' => 'amount'],
            ]],
            'outputs' => [['id' => 'decision']],
            'rules' => [['when' => ['amount' => '*'], 'then' => ['decision' => 'review']]],
        ],
        hitPolicy: 'FIRST',
    ),
    static fn () => new DecisionResultTemplateDescriptor(
        key: 'sample.mismatched-event-input',
        version: '1.0',
        labelKey: 'sample.mismatched-event-input.label',
        fields: [$field],
        decisionTable: [
            'inputs' => [[
                'id' => 'amount',
                'expression' => 'event_payload.total',
                'source' => [
                    'kind' => 'event_payload',
                    'event_name' => 'sample.report.submitted',
                    'payload_key' => 'amount',
                ],
            ]],
            'outputs' => [['id' => 'decision']],
            'rules' => [['when' => ['amount' => '*'], 'then' => ['decision' => 'review']]],
        ],
        hitPolicy: 'FIRST',
    ),
    static fn () => new DecisionResultTemplateDescriptor(
        key: 'sample.mismatched-process-input',
        version: '1.0',
        labelKey: 'sample.mismatched-process-input.label',
        fields: [$field],
        decisionTable: [
            'inputs' => [[
                'id' => 'amount',
                'expression' => 'variables.amount',
                'source' => ['kind' => 'process_variable'],
            ]],
            'outputs' => [['id' => 'decision']],
            'rules' => [['when' => ['amount' => '*'], 'then' => ['decision' => 'review']]],
        ],
        hitPolicy: 'FIRST',
    ),
    static fn () => new ProcessUserTaskFormDescriptor(
        key: 'sample.invalid-slot-output',
        appKey: 'sample',
        labelKey: 'sample.invalid-slot-output.label',
        rendering: [
            'mode' => 'slot_widget',
            'slot' => ProcessUserTaskFormDescriptor::FORM_SLOT,
            'component' => 'sample.invalid-slot-output',
            'slot_api_version' => 1,
        ],
        outputSchema: ['saved.state' => ['type' => 'mystery']],
        submissionActionKey: 'expense-report.save',
    ),
    static fn () => new ProcessUserTaskFormDescriptor(
        key: 'sample.invalid-slot-name',
        appKey: 'sample',
        labelKey: 'sample.invalid-slot-name.label',
        rendering: [
            'mode' => 'slot_widget',
            'slot' => 'sample.private-slot',
            'component' => 'sample.invalid-slot-name',
            'slot_api_version' => 1,
        ],
        submissionActionKey: 'expense-report.save',
    ),
    static fn () => new ProcessUserTaskFormDescriptor(
        key: 'sample.invalid-slot-version',
        appKey: 'sample',
        labelKey: 'sample.invalid-slot-version.label',
        rendering: [
            'mode' => 'slot_widget',
            'slot' => ProcessUserTaskFormDescriptor::FORM_SLOT,
            'component' => 'sample.invalid-slot-version',
            'slot_api_version' => 2,
        ],
        submissionActionKey: 'expense-report.save',
    ),
    static fn () => new ProcessUserTaskFormDescriptor(
        key: 'sample.invalid-output-enum',
        appKey: 'sample',
        labelKey: 'sample.invalid-output-enum.label',
        outputSchema: [
            'decision' => ['type' => 'string', 'enum' => ['approved', 'approved']],
        ],
    ),
    static fn () => new ProcessUserTaskFormDescriptor(
        key: 'sample.incomplete-output-enum-labels',
        appKey: 'sample',
        labelKey: 'sample.incomplete-output-enum-labels.label',
        outputSchema: [
            'decision' => [
                'type' => 'string',
                'enum' => ['approved', 'rejected'],
                'enum_labels' => ['approved' => 'sample.decision.approved'],
            ],
        ],
    ),
    static fn () => new ProcessTemplateDescriptor(
        key: 'sample.blank-owner',
        version: '1.0',
        appKey: ' ',
        labelKey: 'sample.process.blank-owner',
        category: ProcessTemplateDescriptor::CATEGORY_APP,
        structure: ['definitions' => []],
    ),
    static fn () => new ProcessTemplateDescriptor(
        key: 'sample.mismatched-owner',
        version: '1.0',
        appKey: 'other-app',
        labelKey: 'sample.process.mismatched-owner',
        category: ProcessTemplateDescriptor::CATEGORY_APP,
        structure: ['definitions' => []],
    ),
];

foreach ($invalidCases as $invalidCase) {
    try {
        $invalidCase();
        throw new RuntimeException('Invalid process descriptor was accepted.');
    } catch (InvalidArgumentException) {
        // Expected: App packages fail their contract check before activation.
    }
}
