<?php

declare(strict_types=1);

use Nexia\Process\Contracts\ProcessRuntime;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Testing\ApprovalProcessBridgeConformance;

require dirname(__DIR__).'/vendor/autoload.php';

$evidence = true;
$applied = false;
ApprovalProcessBridgeConformance::assert(
    static function (ProcessRuntime $runtime) use (&$applied): void {
        $applied = true;
        $runtime->deliverMessage(new ProcessMessageDelivery(
            messageIdentity: 'sample.approval-outcome:event-1',
            legalEntityKey: 1,
            processInstancePublicId: '00000000-0000-0000-0000-000000000001',
            businessKey: null,
            resourceRef: new ResourceRef('sample', 'sample.report', 'report-1', 'Expense report'),
            elementId: 'approval',
            topic: 'sample.approval_outcome',
            payload: ['approval_outcome' => 'approved'],
        ));
    },
    static fn (): bool => $evidence,
    static function () use (&$applied): bool {
        return $applied;
    },
);

$applied = false;
$violations = ApprovalProcessBridgeConformance::violations(
    static function (ProcessRuntime $runtime): void {
        $runtime->deliverMessage(new ProcessMessageDelivery(
            messageIdentity: 'sample.approval-outcome:event-2',
            legalEntityKey: 1,
            processInstancePublicId: '00000000-0000-0000-0000-000000000001',
            businessKey: null,
            resourceRef: new ResourceRef('sample', 'sample.report', 'report-1', 'Expense report'),
            elementId: 'approval',
            topic: 'sample.approval_outcome',
            payload: ['approval_outcome' => 'approved'],
        ));
    },
    static fn (): bool => false,
    static fn (): bool => false,
);
if (count($violations) !== 2) {
    throw new RuntimeException('A bridge without frozen evidence or applied App state passed conformance.');
}

fwrite(STDOUT, "Approval Process bridge conformance fixture passed.\n");
