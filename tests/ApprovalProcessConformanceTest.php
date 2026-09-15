<?php

declare(strict_types=1);

use Nexia\AppDescriptors\ProcessApprovalTaskConfiguration;
use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\Testing\ApprovalProcessConformance;

require dirname(__DIR__).'/vendor/autoload.php';

$approval = new ProcessApprovalTaskConfiguration(
    bindingKey: 'sample.report.submit',
    outcomeTopic: 'sample.report.approval_outcome',
);
$descriptor = new ProcessWorkActionDescriptor(
    kind: 'serviceTask',
    appKey: 'sample',
    actionKey: 'report.submit_approval',
    labelKey: 'sample.process.report.submit_approval',
    topic: 'sample.report.submit_approval',
    payloadSchema: [
        'template_key' => ['catalog_ref' => 'approval.business_template', 'filter' => ['binding_key' => 'sample.report.submit']],
        'approval_route_policy_key' => ['catalog_ref' => 'approval.route_policy', 'filter' => ['status' => 'active']],
    ],
    approvalTask: $approval,
    outputContract: ['approval_outcome' => [
        'type' => 'string',
        'enum' => $approval->outcomes,
        'label_key' => 'sample.process.report.approval_outcome',
        'enum_labels' => array_combine(
            $approval->outcomes,
            array_map(static fn (string $value): string => "sample.process.report.approval_outcome.{$value}", $approval->outcomes),
        ),
    ]],
);
$structure = ['definitions' => ['processes' => [[
    'flowElements' => [
        [
            'id' => 'approval',
            'type' => 'serviceTask',
            'nexia:topic' => $descriptor->topic,
            'nexia:workAction' => ['app' => 'sample', 'action_key' => 'report.submit_approval'],
            'ioSpecification' => [
                'dataInputs' => [['id' => 'approval_request', 'name' => 'request']],
                'dataOutputs' => [['id' => 'approval_outcome', 'name' => 'approval_outcome']],
            ],
            'dataInputAssociations' => [[
                'sourceRefs' => ['request'],
                'targetRef' => 'approval_request',
                'extensionElements' => ['nexia' => ['sourcePath' => 'variables.request', 'targetPath' => 'request']],
            ]],
            'dataOutputAssociations' => [[
                'sourceRefs' => ['approval_outcome'],
                'targetRef' => 'outcome',
                'extensionElements' => ['nexia' => ['sourcePath' => 'approval_outcome', 'targetPath' => 'variables.approval.outcome']],
            ]],
        ],
        ['id' => 'gateway', 'type' => 'exclusiveGateway'],
        ['id' => 'approval_to_gateway', 'type' => 'sequenceFlow', 'source' => 'approval', 'target' => 'gateway'],
        ['id' => 'approved', 'type' => 'sequenceFlow', 'source' => 'gateway', 'target' => 'end', 'condition' => ['variable' => 'approval.outcome', 'operator' => '==', 'value' => 'approved']],
        ['id' => 'not_approved', 'type' => 'sequenceFlow', 'source' => 'gateway', 'target' => 'rejected', 'default' => true],
        ['id' => 'end', 'type' => 'endEvent'],
        ['id' => 'rejected', 'type' => 'endEvent'],
    ],
]]]];

ApprovalProcessConformance::assert($descriptor, $structure, 'approval');

$broken = $structure;
$broken['definitions']['processes'][0]['flowElements'][0]['dataOutputAssociations'] = [];
if (ApprovalProcessConformance::violations($descriptor, $broken, 'approval') === []) {
    throw new RuntimeException('A missing Approval output association passed conformance.');
}

$duplicated = $structure;
$duplicated['definitions']['processes'][0]['flowElements'][] = ['id' => 'gateway', 'type' => 'exclusiveGateway'];
if (! in_array(
    'The BPMN structure declares duplicate element id [gateway].',
    ApprovalProcessConformance::violations($descriptor, $duplicated, 'approval'),
    true,
)) {
    throw new RuntimeException('A duplicate BPMN element id passed conformance.');
}

fwrite(STDOUT, "Approval Process conformance fixture passed.\n");
