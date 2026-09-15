<?php

declare(strict_types=1);

use Nexia\AppDescriptors\Contracts\AppDescriptor;
use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;
use Nexia\AppDescriptors\Contracts\AppDescriptorSet;
use Nexia\AppDescriptors\ApprovalBusinessTemplatePresetDescriptor;
use Nexia\AppDescriptors\ApprovalDocumentSchema;
use Nexia\AppDescriptors\ApprovalFormBindingDescriptor;
use Nexia\AppDescriptors\ApprovalRoutePolicyPresetDescriptor;
use Nexia\AppDescriptors\CaseCorrelationSubjectDescriptor;
use Nexia\AppDescriptors\DecisionResultTemplateDescriptor;
use Nexia\AppDescriptors\DescriptorStatus;
use Nexia\AppDescriptors\DescriptorValidationException;
use Nexia\AppDescriptors\EventDescriptor;
use Nexia\AppDescriptors\EventPayloadSchema;
use Nexia\AppDescriptors\PerformanceMeasurementDefinition;
use Nexia\AppDescriptors\PerformanceMeasurementSourceCatalogEntry;
use Nexia\AppDescriptors\PerformanceMeasurementSourceDescriptor;
use Nexia\AppDescriptors\PerformanceMeasurementTimeSemantics;
use Nexia\AppDescriptors\PerformanceMeasurementValueType;
use Nexia\AppDescriptors\ProcessStartBindingDescriptor;
use Nexia\AppDescriptors\ProcessTemplateDescriptor;
use Nexia\AppDescriptors\ProcessUserTaskFormDescriptor;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\AppDescriptors\PublicEventPayloadSchema;
use Nexia\AppDescriptors\ReportingViewDescriptor;
use Nexia\AppDescriptors\ResourceActionDescriptor;
use Nexia\AppDescriptors\ResourceActionEffect;
use Nexia\AppDescriptors\ResourceDescriptor;
use Nexia\AppDescriptors\ResourceDescriptorContract;
use Nexia\AppDescriptors\ResourceLifecycleEventDescriptor;
use Nexia\AppDescriptors\ResourceMutationDescriptor;
use Nexia\AppDescriptors\ResourceSummary;
use Nexia\AppDescriptors\ResourceSummaryField;
use Nexia\AppDescriptors\ResourceSummaryFieldRole;
use Nexia\AppDescriptors\SignatureBulkBindingDescriptor;
use Nexia\AppDescriptors\SignatureBulkRoleAssignmentPolicy;
use Nexia\AppDescriptors\SignatureTemplateBindingDescriptor;
use Nexia\AppDescriptors\SlotWidgetDescriptor;
use Nexia\Events\HandoffResultState;
use Nexia\Signature\SignatureAuthenticationMethod;
use Nexia\Signature\SignatureBulkParticipantAssignmentSource;
use Nexia\Signature\SignatureInvitationChannel;

require dirname(__DIR__).'/vendor/autoload.php';

$subject = new CaseCorrelationSubjectDescriptor(
    key: 'expense_report',
    labelKey: 'sample.report.label',
    subjectResourceKey: 'sample.expense_report',
    sourcePath: 'report_id',
);
$event = new ResourceLifecycleEventDescriptor(
    key: 'sample.expense_report.submitted',
    labelKey: 'sample.events.submitted',
    caseCorrelationSubjects: [$subject],
);

$labelledPayload = PublicEventPayloadSchema::withLabelKeys([
    'report_id' => ['type' => 'string'],
    'lines' => [
        'type' => 'array',
        'items' => [
            'type' => 'object',
            'properties' => [
                'amount' => ['type' => 'number'],
            ],
        ],
    ],
], 'sample.events.fields');

$publicEvent = new ResourceLifecycleEventDescriptor(
    key: 'submitted',
    labelKey: 'sample.events.submitted',
    payloadSchema: $labelledPayload,
    publicForComposition: true,
);
$resource = new ResourceDescriptor(
    key: 'sample.expense_report',
    version: '1.0',
    lifecycleEvents: [$event],
    actions: [new ResourceActionDescriptor(
        key: 'approval.submit',
        permission: 'sample.expense_report.submit',
        method: 'POST',
        path: '/api/legal-entities/{legalEntity:public_id}/sample/expense-reports/{expenseReport:public_id}/submit',
        inputSchema: ['type' => 'object', 'additionalProperties' => false],
        effect: ResourceActionEffect::Mutate,
    )],
    mutation: new ResourceMutationDescriptor(
        createInputSchema: ['type' => 'object', 'properties' => ['title' => ['type' => 'string']]],
    ),
);

$descriptors = new class implements AppDescriptorContribution
{
    public static AppDescriptorSet $descriptors;

    public static function appDescriptors(): AppDescriptorSet
    {
        return self::$descriptors;
    }
};

$widget = new SlotWidgetDescriptor(
    key: 'sample.expense_report.summary',
    version: '1.0',
    slot: 'approval.composer.business_form',
    component: 'ExpensesReportSummary',
    slotApiVersion: 1,
    permission: 'sample.expense-report.read',
    familyKey: 'sample.expense-report',
);
$descriptors::$descriptors = AppDescriptorSet::of($resource, $widget);

foreach ([
    ResourceDescriptor::class,
    SlotWidgetDescriptor::class,
    ReportingViewDescriptor::class,
    DecisionResultTemplateDescriptor::class,
    EventDescriptor::class,
    ProcessStartBindingDescriptor::class,
    ProcessTemplateDescriptor::class,
    ProcessUserTaskFormDescriptor::class,
    ProcessWorkActionDescriptor::class,
    ApprovalFormBindingDescriptor::class,
    ApprovalDocumentSchema::class,
    ApprovalBusinessTemplatePresetDescriptor::class,
    ApprovalRoutePolicyPresetDescriptor::class,
    PerformanceMeasurementSourceDescriptor::class,
    SignatureBulkBindingDescriptor::class,
    SignatureTemplateBindingDescriptor::class,
] as $descriptorClass) {
    if (! is_a($descriptorClass, AppDescriptor::class, true)) {
        throw new RuntimeException("App descriptor [{$descriptorClass}] must implement ".AppDescriptor::class.'.');
    }
}

$bulkDescriptor = new SignatureBulkBindingDescriptor(
    appKey: 'sample',
    bindingKey: 'sample.expense_report',
    bindingVersion: '1.0',
    capabilityContractVersion: 1,
    supportedSubjectResourceKeys: ['sample.expense_report'],
    roleAssignmentPolicies: [new SignatureBulkRoleAssignmentPolicy(
        'signer',
        [SignatureBulkParticipantAssignmentSource::RequestSupplied],
    )],
    supportedInvitationChannels: [SignatureInvitationChannel::Email],
    supportedAuthenticationMethods: [SignatureAuthenticationMethod::EmailOtp],
);
if (SignatureBulkBindingDescriptor::fromArray($bulkDescriptor->toArray())->toArray() !== $bulkDescriptor->toArray()
    || AppDescriptorSet::of($bulkDescriptor)->ofType(SignatureBulkBindingDescriptor::class) !== [$bulkDescriptor]) {
    throw new RuntimeException('Signature bulk binding descriptors must round-trip through the common descriptor catalog.');
}
try {
    SignatureBulkBindingDescriptor::fromArray([...$bulkDescriptor->toArray(), 'provider_class' => stdClass::class]);
    throw new RuntimeException('Signature bulk descriptors must reject runtime implementation metadata.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

$summaryField = new ResourceSummaryField(
    key: 'status',
    value: 'submitted',
    type: 'enum',
    labelKey: 'sample.report.status',
    enumLabels: ['submitted' => 'sample.status.submitted'],
    role: ResourceSummaryFieldRole::Status,
);
$summary = new ResourceSummary(
    display: 'Expense Report ER-001',
    route: '/apps/sample/reports/ER-001',
    fields: [$summaryField],
    icon: 'receipt',
);

if ($descriptors::appDescriptors()->all() !== [$resource, $widget]
    || $descriptors::appDescriptors()->ofType(ResourceDescriptor::class) !== [$resource]
    || $descriptors::appDescriptors()->ofType(SlotWidgetDescriptor::class) !== [$widget]
    || $resource->status !== DescriptorStatus::Active
    || $resource->lifecycleEvents[0]->caseCorrelationSubjects[0] !== $subject
    || $resource->actions[0]->key !== 'approval.submit'
    || $resource->mutation?->createInputSchema['properties']['title']['type'] !== 'string'
    || $publicEvent->payloadSchema['report_id']['labelKey'] !== 'sample.events.fields.report_id'
    || $publicEvent->payloadSchema['lines']['items']['properties']['amount']['labelKey'] !== 'sample.events.fields.amount'
    || $summary->fields !== [$summaryField]
    || $summary->fields[0]->enumLabels !== ['submitted' => 'sample.status.submitted']
    || $summary->fields[0]->role !== ResourceSummaryFieldRole::Status
    || $summary->icon !== 'receipt'
) {
    throw new RuntimeException('App descriptor contracts changed unexpectedly.');
}

$completedOrders = new PerformanceMeasurementDefinition(
    key: 'completed_orders',
    metricVersion: 'FY26-R3',
    labelKey: 'sample-source.measurements.completed_orders',
    unitCode: 'count',
    valueType: PerformanceMeasurementValueType::Integer,
    timeSemantics: PerformanceMeasurementTimeSemantics::OccurredAt,
);
$recognizedRevenue = new PerformanceMeasurementDefinition(
    key: 'recognized_revenue',
    metricVersion: 'FY26-R3',
    labelKey: 'sample-source.measurements.recognized_revenue',
    unitCode: 'KRW',
    valueType: PerformanceMeasurementValueType::Decimal,
    timeSemantics: PerformanceMeasurementTimeSemantics::Period,
);
$measurementSource = new PerformanceMeasurementSourceDescriptor(
    resourceKey: 'sample-source.order',
    version: '1.0',
    measurements: [$completedOrders, $recognizedRevenue],
);
$measurementCatalogEntry = new PerformanceMeasurementSourceCatalogEntry(
    ownerAppKey: 'sample-source',
    resourceLabelKey: 'sample-source.order.list.title',
    descriptor: $measurementSource,
);

if ($measurementSource->descriptorKey() !== 'sample-source.order'
    || $measurementSource->version !== '1.0'
    || $measurementSource->status !== DescriptorStatus::Active
    || $measurementSource->measurements !== [$completedOrders, $recognizedRevenue]
    || $completedOrders->subjectResourceKey !== 'directory.party'
    || $completedOrders->sourceLegalEntityResourceKey !== 'enterprise.legal_entity'
    || AppDescriptorSet::of($measurementSource)->ofType(PerformanceMeasurementSourceDescriptor::class) !== [$measurementSource]
    || $measurementCatalogEntry->ownerAppKey !== 'sample-source'
    || $measurementCatalogEntry->resourceLabelKey !== 'sample-source.order.list.title'
    || $measurementCatalogEntry->descriptor !== $measurementSource) {
    throw new RuntimeException('Performance measurement descriptors changed unexpectedly.');
}

try {
    AppDescriptorSet::of($measurementSource, new PerformanceMeasurementSourceDescriptor(
        resourceKey: 'sample-source.order',
        version: '1.1',
        measurements: [$completedOrders],
    ));
    throw new RuntimeException('Only one performance measurement source may be declared per Resource.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

foreach ([
    fn () => new PerformanceMeasurementSourceDescriptor('sample-source.order', '', [$completedOrders]),
    fn () => new PerformanceMeasurementSourceDescriptor('sample-source.order', '1.0', []),
    fn () => new PerformanceMeasurementSourceDescriptor('sample-other.item', '1.0', [$completedOrders]),
    fn () => new PerformanceMeasurementSourceDescriptor('sample-source.order', '1.0', [$completedOrders, $completedOrders]),
    fn () => new PerformanceMeasurementSourceCatalogEntry('sample-other', null, $measurementSource),
    fn () => new PerformanceMeasurementSourceCatalogEntry('sample-source', ' sample-source.order.list.title', $measurementSource),
    fn () => new PerformanceMeasurementDefinition(
        key: 'wrong_subject',
        metricVersion: 'FY26-R3',
        labelKey: 'sample-source.measurements.wrong_subject',
        unitCode: 'count',
        valueType: PerformanceMeasurementValueType::Integer,
        timeSemantics: PerformanceMeasurementTimeSemantics::OccurredAt,
        subjectResourceKey: 'sample-owner.worker',
    ),
    fn () => new PerformanceMeasurementDefinition(
        key: 'missing_version',
        metricVersion: '',
        labelKey: 'sample-source.measurements.missing_version',
        unitCode: 'count',
        valueType: PerformanceMeasurementValueType::Integer,
        timeSemantics: PerformanceMeasurementTimeSemantics::OccurredAt,
    ),
] as $invalidMeasurement) {
    try {
        $invalidMeasurement();
        throw new RuntimeException('Invalid performance measurement semantics must be rejected.');
    } catch (InvalidArgumentException) {
        // Expected contract validation.
    }
}

try {
    new SlotWidgetDescriptor(
        key: 'invalid',
        version: '1.0',
        slot: 'invalid',
        component: 'Invalid',
        slotApiVersion: 1,
        familyKey: '',
    );
    throw new RuntimeException('Empty widget family keys must be rejected.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

$partyField = [
    'type' => 'resource_reference',
    'accepted_resource_keys' => ['directory.party'],
    'selector_purpose' => 'sample.owner.select',
    'selector_permissions' => ['sample.expense_report.update'],
    'party_selection' => [
        'eligibility' => 'active_legal_entity_member',
        'types' => ['person'],
    ],
];
ResourceDescriptorContract::assertValid('PartyField', [new ResourceDescriptor(
    key: 'sample.expense_report',
    version: '1.0',
    fieldSchema: ['owner_party_public_id' => $partyField],
)]);
$partyFieldWithoutConstraint = $partyField;
unset($partyFieldWithoutConstraint['party_selection']);

foreach ([
    $partyFieldWithoutConstraint,
    [...$partyFieldWithoutConstraint, 'accepted_resource_keys' => ['directory.party', 'sample.owner']],
    [...$partyField, 'party_selection' => ['types' => [], 'eligibility' => 'none']],
    [...$partyField, 'party_selection' => ['types' => ['person', 'person'], 'eligibility' => 'none']],
    [...$partyField, 'party_selection' => ['types' => ['worker'], 'eligibility' => 'none']],
    [...$partyField, 'party_selection' => ['types' => ['person'], 'eligibility' => 'active_worker']],
    [...$partyField, 'party_selection' => ['types' => ['person'], 'eligibility' => 'none', 'future' => true]],
    [...$partyField, 'accepted_resource_keys' => ['sample-owner.worker']],
    ['type' => 'string', 'party_selection' => ['types' => ['person'], 'eligibility' => 'none']],
] as $invalidPartyField) {
    try {
        ResourceDescriptorContract::assertValid('InvalidPartyField', [new ResourceDescriptor(
            key: 'sample.expense_report',
            version: '1.0',
            fieldSchema: ['owner_party_public_id' => $invalidPartyField],
        )]);
        throw new RuntimeException('Invalid party_selection declarations must be rejected.');
    } catch (DescriptorValidationException) {
        // Expected contract validation.
    }
}

try {
    new ResourceLifecycleEventDescriptor(
        key: 'submitted',
        labelKey: 'sample.events.submitted',
        payloadSchema: ['report_id' => ['type' => 'string']],
        publicForComposition: true,
    );
    throw new RuntimeException('Unlabelled public event payload nodes must be rejected.');
} catch (DescriptorValidationException $exception) {
    if ($exception->path !== 'report_id') {
        throw new RuntimeException('Descriptor validation must identify the invalid payload path.');
    }
}

new ResourceLifecycleEventDescriptor(
    key: 'internal_only',
    labelKey: '',
    payloadSchema: ['report_id' => ['type' => 'string']],
    publicForComposition: false,
);

new ResourceLifecycleEventDescriptor(
    key: 'internal_handoff',
    labelKey: '',
    payloadSchema: [
        'result_state' => [
            'type' => 'string',
            'enum' => HandoffResultState::values(
                HandoffResultState::Accepted,
                HandoffResultState::TerminalFailed,
            ),
        ],
    ],
    publicForComposition: false,
);

foreach ([
    ['type' => 'string'],
    ['type' => 'string', 'enum' => ['ACCEPTED', 'FAILED']],
    ['type' => 'string', 'enum' => ['ACCEPTED', 'ACCEPTED']],
] as $invalidResultState) {
    try {
        new ResourceLifecycleEventDescriptor(
            key: 'invalid_handoff',
            labelKey: '',
            payloadSchema: ['result_state' => $invalidResultState],
            publicForComposition: false,
        );
        throw new RuntimeException('Handoff result states must declare a canonical enum subset.');
    } catch (DescriptorValidationException $exception) {
        if ($exception->path !== 'result_state') {
            throw new RuntimeException('Handoff result validation must identify the result_state path.');
        }
    }
}

$aliasEvent = new ResourceLifecycleEventDescriptor(
    key: 'legacy_aliases',
    labelKey: 'sample.events.legacy_aliases',
    payloadSchema: [
        'legacy' => [
            'type' => 'string',
            'label_key' => 'sample.events.fields.legacy',
        ],
    ],
    publicForComposition: true,
);

if ($aliasEvent->payloadSchema['legacy']['label_key'] !== 'sample.events.fields.legacy') {
    throw new RuntimeException('Legacy payload label aliases must remain readable.');
}

foreach ([
    ['lines' => ['type' => 'array', 'labelKey' => 'sample.events.fields.lines', 'items' => ['type' => 'string']]],
    ['subject' => ['type' => 'object', 'labelKey' => 'sample.events.fields.subject', 'properties' => ['id' => ['type' => 'string']]]],
    ['result' => ['labelKey' => 'sample.events.fields.result', 'oneOf' => [['type' => 'string']]]],
] as $invalidNestedPayload) {
    try {
        new ResourceLifecycleEventDescriptor(
            key: 'nested_invalid',
            labelKey: 'sample.events.nested_invalid',
            payloadSchema: $invalidNestedPayload,
            publicForComposition: true,
        );
        throw new RuntimeException('Every nested public event payload node must require a label.');
    } catch (DescriptorValidationException) {
        // Expected contract validation.
    }
}

try {
    ResourceDescriptorContract::assertValid('InvalidResources', [new stdClass]);
    throw new RuntimeException('Resource descriptor results must contain only SDK descriptors.');
} catch (DescriptorValidationException) {
    // Expected contract validation.
}

try {
    ResourceDescriptorContract::assertValid('InvalidReferenceField', [new ResourceDescriptor(
        key: 'sample.expense_report',
        version: '1.0',
        fieldSchema: ['worker' => ['type' => 'resource_reference']],
    )]);
    throw new RuntimeException('Resource Reference fields must declare their selector contract.');
} catch (DescriptorValidationException) {
    // Expected contract validation.
}

foreach ([
    new ResourceDescriptor(
        key: 'sample.report..line',
        version: '1.0',
        fieldSchema: [],
    ),
    new ResourceDescriptor(
        key: 'sample.'.str_repeat('x', 154),
        version: '1.0',
        fieldSchema: [],
    ),
    new ResourceDescriptor(
        key: 'sample.expense_report',
        version: '1.0',
        fieldSchema: [str_repeat('x', 161) => [
            'type' => 'resource_reference',
            'accepted_resource_keys' => ['sample-owner.worker'],
            'selector_purpose' => 'sample.reference-options',
            'selector_permissions' => ['sample.expense_report.create'],
        ]],
    ),
    new ResourceDescriptor(
        key: 'sample.expense_report',
        version: '1.0',
        fieldSchema: ['worker' => [
            'type' => 'resource_reference',
            'accepted_resource_keys' => ['sample-owner.worker'],
            'selector_purpose' => 'sample.'.str_repeat('x', 154),
            'selector_permissions' => ['sample.expense_report.create'],
        ]],
    ),
] as $invalidBoundedSelector) {
    try {
        ResourceDescriptorContract::assertValid('InvalidBoundedSelector', [$invalidBoundedSelector]);
        throw new RuntimeException('Selector descriptor identifiers must fit their runtime boundary.');
    } catch (DescriptorValidationException) {
        // Expected contract validation.
    }
}

try {
    AppDescriptorSet::of($resource, $resource);
    throw new RuntimeException('Duplicate App descriptors must be rejected by concrete type and key.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

foreach ([' sample.padded', 'sample.padded '] as $paddedKey) {
    try {
        AppDescriptorSet::of(new ResourceDescriptor(key: $paddedKey, version: '1.0'));
        throw new RuntimeException('App descriptor keys with surrounding whitespace must be rejected.');
    } catch (InvalidArgumentException) {
        // Expected contract validation.
    }
}

if (AppDescriptorSet::empty()->all() !== []
    || AppDescriptorSet::from((static function () use ($resource, $widget): iterable {
        yield $resource;
        yield $widget;
    })())->all() !== [$resource, $widget]
) {
    throw new RuntimeException('App descriptor sets must preserve empty and iterable construction semantics.');
}

try {
    AppDescriptorSet::from([new stdClass]);
    throw new RuntimeException('App descriptor sets must reject untyped values.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

$standaloneEvent = new EventDescriptor(
    key: 'sample-owner.onboarding.started',
    schemaVersion: 1,
    aggregateType: 'sample-owner.onboarding_case',
    payloadSchema: [
        'case_id' => ['type' => 'string', 'format' => 'uuid'],
        'source' => ['type' => 'string', 'enum' => ['manual', 'import']],
    ],
);
$composableEvent = new EventDescriptor(
    key: 'approval.case.completed',
    schemaVersion: 2,
    aggregateType: 'approval.case',
    payloadSchema: [
        'case_id' => [
            'type' => 'string',
            'format' => 'uuid',
            'labelKey' => 'approval.events.fields.case_id',
        ],
    ],
    status: DescriptorStatus::Deprecated,
    publicForComposition: true,
    labelKey: 'approval.events.completed',
    replacementEventName: 'approval.case.closed',
);

if ($standaloneEvent->descriptorKey() !== 'sample-owner.onboarding.started'
    || $standaloneEvent->status !== DescriptorStatus::Active
    || $standaloneEvent->publicForComposition
    || $standaloneEvent->labelKey !== null
    || $composableEvent->schemaVersion !== 2
    || $composableEvent->replacementEventName !== 'approval.case.closed'
    || $event->schemaVersion !== 1
    || (new ResourceLifecycleEventDescriptor(
        key: 'versioned',
        labelKey: '',
        schemaVersion: 2,
    ))->schemaVersion !== 2
    || AppDescriptorSet::of($standaloneEvent)->ofType(EventDescriptor::class) !== [$standaloneEvent]
) {
    throw new RuntimeException('Public Event descriptors changed unexpectedly.');
}

foreach ([
    fn () => new EventDescriptor('invalid', 1, 'sample-owner.case', []),
    fn () => new EventDescriptor('sample-owner.valid', 0, 'sample-owner.case', []),
    fn () => new EventDescriptor('sample-owner.valid', 1, ' sample-owner.case', []),
    fn () => new EventDescriptor('sample-owner.valid', 1, 'sample-owner', []),
    fn () => new EventDescriptor('sample-owner.valid', 1, 'sample-owner.case', [], publicForComposition: true),
    fn () => new EventDescriptor('sample-owner.valid', 1, 'sample-owner.case', [], labelKey: ' '),
    fn () => new EventDescriptor('sample-owner.valid', 1, 'sample-owner.case', [], replacementEventName: 'invalid'),
    fn () => new EventDescriptor('sample-owner.valid', 1, 'sample-owner.case', [], replacementEventName: 'sample-owner.valid'),
    fn () => new ResourceLifecycleEventDescriptor('invalid_version', '', schemaVersion: 0),
] as $invalidEventDescriptor) {
    try {
        $invalidEventDescriptor();
        throw new RuntimeException('Invalid public Event descriptor contracts must be rejected.');
    } catch (InvalidArgumentException) {
        // Expected contract validation.
    }
}

$payloadSchema = [
    'event_id' => ['type' => 'string', 'format' => 'uuid'],
    'state' => ['type' => 'string', 'enum' => ['started', 'completed']],
    'occurred_at' => ['type' => 'string', 'format' => 'date-time'],
    'effective_on' => ['type' => 'string', 'format' => 'date'],
    'approved' => ['type' => 'boolean'],
    'amount' => ['type' => 'number'],
    'opaque_reference' => ['type' => 'object'],
    'metadata' => [
        'type' => 'object',
        'properties' => [
            'note' => ['type' => 'string', 'nullable' => true, 'required' => false],
        ],
    ],
    'lines' => [
        'type' => 'array',
        'items' => [
            'type' => 'object',
            'properties' => [
                'quantity' => ['type' => 'integer'],
            ],
        ],
    ],
];
$validPayload = [
    'event_id' => '123e4567-e89b-12d3-a456-426614174000',
    'state' => 'started',
    'occurred_at' => '2026-09-05T12:34:56+09:00',
    'effective_on' => '2026-09-05',
    'approved' => true,
    'amount' => 12.5,
    'opaque_reference' => ['app_key' => 'sample-owner', 'resource_id' => 'worker-1'],
    'metadata' => [],
    'lines' => [['quantity' => 2]],
];

EventPayloadSchema::assertPayload('sample-owner.onboarding.started', $payloadSchema, $validPayload);
EventPayloadSchema::assertPayload('sample-owner.onboarding.started', $payloadSchema, [
    ...$validPayload,
    'metadata' => ['note' => null],
]);

foreach ([
    ['field' => ['type' => 'date']],
    ['field' => ['type' => 'string', 'nullable' => 'yes']],
    ['field' => ['type' => 'string', 'required' => 1]],
    ['field' => ['type' => 'integer', 'enum' => [1, '2']]],
    ['field' => ['type' => 'string', 'enum' => []]],
    ['field' => ['type' => 'integer', 'format' => 'date']],
    ['field' => ['type' => 'string', 'format' => 'email']],
    ['field' => ['type' => 'string', 'properties' => []]],
    ['field' => ['type' => 'object', 'propeties' => []]],
    ['field' => ['type' => 'object', 'properties' => ['child' => 'string']]],
    ['field' => ['type' => 'string', 'items' => ['type' => 'string']]],
    ['field' => ['type' => 'array', 'items' => ['type' => 'resource_reference']]],
] as $invalidSchema) {
    try {
        EventPayloadSchema::assertValid('sample-owner.onboarding.started', $invalidSchema);
        throw new RuntimeException('Invalid Event payload schema structures must be rejected.');
    } catch (DescriptorValidationException) {
        // Expected contract validation.
    }
}

$invalidPayloads = [
    array_diff_key($validPayload, ['state' => true]),
    [...$validPayload, 'undeclared' => true],
    [...$validPayload, 'event_id' => 'not-a-uuid'],
    [...$validPayload, 'state' => 'unknown'],
    [...$validPayload, 'effective_on' => '2026-02-30'],
    [...$validPayload, 'occurred_at' => '2026-02-30T12:34:56+09:00'],
    [...$validPayload, 'approved' => 1],
    [...$validPayload, 'amount' => '12.5'],
    [...$validPayload, 'metadata' => ['undeclared' => true]],
    [...$validPayload, 'lines' => [['quantity' => 1.5]]],
    [...$validPayload, 'lines' => ['quantity' => 2]],
];

foreach ($invalidPayloads as $invalidPayload) {
    try {
        EventPayloadSchema::assertPayload('sample-owner.onboarding.started', $payloadSchema, $invalidPayload);
        throw new RuntimeException('Invalid Event payload values must be rejected.');
    } catch (DescriptorValidationException) {
        // Expected contract validation.
    }
}

fwrite(STDOUT, "App descriptor contracts are valid.\n");
