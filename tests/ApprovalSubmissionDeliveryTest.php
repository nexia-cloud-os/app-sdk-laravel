<?php

declare(strict_types=1);

use Nexia\Approval\ApprovalSubmissionDelivery;
use Nexia\Approval\ApprovalSubmissionReceipt;

require dirname(__DIR__).'/vendor/autoload.php';

assert((new ApprovalSubmissionReceipt('submission-1'))->publicId === 'submission-1');
assert((new ApprovalSubmissionDelivery('submission-1', 'commit', 'case-1'))->casePublicId === 'case-1');
assert((new ApprovalSubmissionDelivery('submission-1', 'release'))->casePublicId === null);
foreach ([['commit', null], ['release', 'case-1'], ['completed', null]] as [$outcome, $caseId]) {
    try {
        new ApprovalSubmissionDelivery('submission-1', $outcome, $caseId);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Invalid delivery outcome/evidence combination was accepted.');
}
echo "Approval submission outcome and evidence contracts passed.\n";
