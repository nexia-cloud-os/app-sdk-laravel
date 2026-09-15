<?php

declare(strict_types=1);

use Nexia\AppDescriptors\ProcessWorkActionDescriptor;
use Nexia\Process\Contracts\ProcessRuntime;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Testing\ReceiveTaskCorrelationConformance;

require dirname(__DIR__).'/vendor/autoload.php';

$descriptor = new ProcessWorkActionDescriptor(
    kind: 'receiveTask',
    appKey: 'sample',
    actionKey: 'report.response',
    labelKey: 'sample.report.response',
    topic: 'sample.report.response',
);
$structure = ['elements' => [[
    'id' => 'await_response',
    'type' => 'receiveTask',
    'nexia:topic' => $descriptor->topic,
    'nexia:workAction' => ['app' => 'sample', 'action_key' => 'report.response'],
    'ioSpecification' => ['dataInputs' => [
        ['id' => 'response_input', 'name' => 'response'],
    ]],
    'dataInputAssociations' => [[
        'targetRef' => 'response_input',
        'extensionElements' => ['nexia' => [
            'sourcePath' => 'response',
            'targetPath' => 'variables.report.response',
        ]],
    ]],
]]];

ReceiveTaskCorrelationConformance::assert(
    $descriptor,
    $structure,
    'await_response',
    ['response'],
    static function (ProcessRuntime $runtime): void {
        $runtime->deliverMessage(new ProcessMessageDelivery(
            messageIdentity: 'sample.report-response:event-1',
            legalEntityKey: 1,
            processInstancePublicId: '00000000-0000-0000-0000-000000000001',
            businessKey: null,
            resourceRef: new ResourceRef('sample', 'sample.report', 'report-1', 'Expense report'),
            elementId: 'await_response',
            topic: 'sample.report.response',
            payload: ['response' => 'accepted'],
        ));
    },
);

$broken = $structure;
$broken['elements'][0]['dataInputAssociations'] = [];
if (ReceiveTaskCorrelationConformance::violations(
    $descriptor,
    $broken,
    'await_response',
    ['response'],
    static fn (ProcessRuntime $runtime) => null,
) === []) {
    throw new RuntimeException('A receive task without IO mapping or App correlation passed conformance.');
}

fwrite(STDOUT, "Receive Task correlation conformance fixture passed.\n");
