<?php

declare(strict_types=1);

use Nexia\Process\Contracts\ProcessRuntime;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Testing\ApprovalOutcomeConsumerConformance;

require dirname(__DIR__).'/vendor/autoload.php';

$instanceResource = new ResourceRef('sample-workflow', 'sample-workflow.screening_evaluation', 'evaluation-1', 'Evaluation');
$approvalSubject = new ResourceRef('sample-workflow', 'sample-workflow.job_offer_revision', 'revision-1', 'Offer revision');
$consumer = static function (ResourceRef $messageScope): Closure {
    return static function (ProcessRuntime $runtime) use ($messageScope): void {
        $runtime->deliverMessage(new ProcessMessageDelivery(
            messageIdentity: 'sample-workflow.approval-outcome:event-1',
            legalEntityKey: 1,
            processInstancePublicId: '00000000-0000-0000-0000-000000000001',
            businessKey: null,
            resourceRef: $messageScope,
            elementId: 'submit_offer_approval',
            topic: 'sample-workflow.offer.approval_outcome',
            payload: ['approval_outcome' => 'approved'],
        ));
    };
};

ApprovalOutcomeConsumerConformance::assert($consumer($instanceResource), $instanceResource);

$violations = ApprovalOutcomeConsumerConformance::violations($consumer($approvalSubject), $instanceResource);
if (! in_array(
    'The consumer delivered the Approval document subject as Process message scope. Process messages must carry the originating Process instance resource reference; validate the downstream Approval subject separately before delivery.',
    $violations,
    true,
)) {
    throw new RuntimeException('An Approval outcome using its document subject as Process message scope passed conformance.');
}

fwrite(STDOUT, "Approval outcome consumer conformance fixture passed.\n");
