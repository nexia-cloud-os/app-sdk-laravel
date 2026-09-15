<?php

declare(strict_types=1);

use Nexia\AppDescriptors\ApprovalBusinessTemplatePresetDescriptor;
use Nexia\AppDescriptors\ApprovalRoutePolicyPresetDescriptor;
use Nexia\AppDescriptors\ProcessApprovalTaskConfiguration;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\Approval\Domain\ApprovalRoutePolicyStep;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Process\Domain\Enums\ProcessInstanceStatus;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\Process\ProcessMessageIdentity;
use Nexia\Process\ProcessWorkActionInvocation;
use Nexia\Process\ProcessWorkActionResult;
use Nexia\ResourceReference\ResourceRef;

require dirname(__DIR__).'/vendor/autoload.php';

$actor = new class implements Actor
{
    public function key(): int|string
    {
        return 1;
    }

    public function publicId(): string
    {
        return 'actor-1';
    }

    public function displayLabel(): string
    {
        return 'Actor';
    }

    public function partyKey(): int|string|null
    {
        return null;
    }

    public function isVerified(): bool
    {
        return true;
    }
};
$legalEntity = new class implements LegalEntity
{
    public function key(): int|string
    {
        return 10;
    }

    public function publicId(): string
    {
        return 'entity-1';
    }

    public function displayLabel(): string
    {
        return 'Entity';
    }

    public function code(): string
    {
        return 'LE';
    }

    public function partyPublicId(): ?string
    {
        return null;
    }

    public function isActiveOrganization(): bool
    {
        return true;
    }
};
$arguments = ['sample', 'approval', '1.0', 'instance-1', 12, 'execution-12', $legalEntity, $actor, new ResourceRef('sample', 'sample.record', 'record-1', 'Record'), []];
$legacyInvocation = new ProcessWorkActionInvocation(...$arguments);
$invocation = new ProcessWorkActionInvocation(...[...$arguments, 'user_renamed_activity']);
if ($legacyInvocation->elementId !== null || $invocation->elementId !== 'user_renamed_activity'
    || $invocation->externalTaskKey !== 12) {
    throw new RuntimeException('Work-action origin or compatible construction was lost.');
}

$approvalTask = new ProcessApprovalTaskConfiguration(
    bindingKey: 'sample.expense_report.submit',
    outcomeTopic: 'sample.expense_report.approval_outcome',
    businessTemplatePresetKey: 'sample.expense_report',
    routePolicyPresetKey: 'sample.manager_approval',
);
$descriptor = new ProcessWorkActionDescriptor(
    kind: 'serviceTask',
    appKey: 'sample',
    actionKey: 'expense_report.approval',
    labelKey: 'sample.process_actions.expense_report.approval',
    topic: 'sample.expense_report.approval',
    payloadSchema: [
        'template_key' => [
            'type' => 'string',
            'required' => true,
            'catalog_ref' => 'approval.business_template',
            'filter' => ['binding_key' => 'sample.expense_report.submit', 'status' => 'published'],
        ],
        'approval_route_policy_key' => [
            'type' => 'string',
            'required' => true,
            'catalog_ref' => 'approval.route_policy',
            'filter' => ['status' => 'active'],
        ],
    ],
    approvalTask: $approvalTask,
    outputContract: [
        'approval_case_id' => ['type' => 'string', 'nullable' => true],
        'approval_outcome' => ['type' => 'string', 'enum' => $approvalTask->outcomes],
    ],
);

if ($descriptor->approvalTask?->bindingKey !== 'sample.expense_report.submit') {
    throw new RuntimeException('Approval Task configuration was not retained.');
}
if (($descriptor->approvalTask?->toArray()['route_policy_preset_key'] ?? null) !== 'sample.manager_approval') {
    throw new RuntimeException('Approval Task authoring preset hints were not retained.');
}
// An approval line cannot be resolved while authoring, so the configuration
// carries no preview target for the host to search a sample record with.
if (array_key_exists('preview_resource_key', $descriptor->approvalTask?->toArray() ?? [])) {
    throw new RuntimeException('Approval Task configuration still serializes a preview Resource Reference key.');
}

$result = ProcessWorkActionResult::waitingForApproval(
    ['approval_case_id' => 'approval-case-1'],
    $approvalTask->outcomeTopic,
    'approval-case-1',
    ['submitted_at' => '2026-08-04T12:00:00+09:00'],
);
if ($result->suspension?->phase !== 'awaiting_approval') {
    throw new RuntimeException('Approval Task result did not enter the waiting phase.');
}

$delivery = new ProcessMessageDelivery(
    messageIdentity: ProcessMessageIdentity::forEvent('sample', 'expense_report.approval_outcome', 'approval-event-1'),
    legalEntityKey: 10,
    processInstancePublicId: 'process-1',
    businessKey: 'expense-1',
    resourceRef: new ResourceRef('sample', 'sample.expense_report', 'expense-1', 'Expense report 1'),
    elementId: 'expense_approval',
    topic: $approvalTask->outcomeTopic,
    payload: ['outcome' => 'approved'],
    referenceId: 'approval-case-1',
    requiredInstanceStatus: ProcessInstanceStatus::Running,
);
if ($delivery->messageIdentity !== 'sample.expense_report.approval_outcome:approval-event-1') {
    throw new RuntimeException('Durable Process message identity was not retained.');
}

try {
    new ProcessMessageDelivery(
        messageIdentity: 'approval-event-without-namespace',
        legalEntityKey: 10,
        processInstancePublicId: 'process-1',
        businessKey: null,
        resourceRef: new ResourceRef('sample', 'sample.expense_report', 'expense-1', 'Expense report 1'),
        elementId: 'expense_approval',
        topic: $approvalTask->outcomeTopic,
        payload: [],
    );
    throw new RuntimeException('A non-namespaced Process message identity was accepted.');
} catch (InvalidArgumentException) {
    // Expected.
}

$templatePreset = new ApprovalBusinessTemplatePresetDescriptor(
    appKey: 'sample',
    presetKey: 'expense_report',
    bindingKey: 'sample.expense_report.submit',
    nameKey: 'sample.approval_presets.expense_report.name',
);
$routePreset = new ApprovalRoutePolicyPresetDescriptor(
    appKey: 'sample',
    presetKey: 'manager_approval',
    nameKey: 'sample.approval_route_presets.manager.name',
    steps: [new ApprovalRoutePolicyStep('sample.manager')],
);
if ($templatePreset->key !== 'sample.expense_report' || $routePreset->key !== 'sample.manager_approval') {
    throw new RuntimeException('Approval preset keys are not stable.');
}

try {
    new ProcessWorkActionDescriptor(
        kind: 'receiveTask',
        appKey: 'sample',
        actionKey: 'invalid',
        labelKey: 'sample.invalid',
        topic: 'sample.invalid',
        payloadSchema: $descriptor->payloadSchema,
        approvalTask: $approvalTask,
    );
    throw new RuntimeException('A non-service Approval Task was accepted.');
} catch (InvalidArgumentException) {
    // Expected.
}

fwrite(STDOUT, "Process Approval Task contracts passed.\n");
