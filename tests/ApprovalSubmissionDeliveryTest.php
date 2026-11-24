<?php

declare(strict_types=1);

use Nexia\Approval\ApprovalCaseReference;
use Nexia\Approval\ApprovalSubmissionDelivery;
use Nexia\Approval\ApprovalSubmissionReceipt;
use Nexia\Approval\ApprovalWire;
use Nexia\Approval\CurrentBoundApprovalResult;

require dirname(__DIR__).'/vendor/autoload.php';

assert((new ApprovalSubmissionReceipt('submission-1'))->publicId === 'submission-1');
assert((new ApprovalSubmissionDelivery('submission-1', 'commit', 'case-1'))->casePublicId === 'case-1');
assert((new ApprovalSubmissionDelivery('submission-1', 'release'))->casePublicId === null);
$result = new CurrentBoundApprovalResult(new ApprovalCaseReference('case-1'), null, 'fixture.template', 2, 'fixture.submit', '1.0');
$restored = ApprovalWire::restoreResult(ApprovalWire::result($result));
assert((new ApprovalSubmissionDelivery('submission-1', 'commit', 'case-1', $restored))->currentResult == $result);
foreach ([['commit', null, null], ['release', 'case-1', null], ['completed', null, null],
    ['release', null, $result], ['commit', 'other-case', $result]] as [$outcome, $caseId, $currentResult]) {
    try {
        new ApprovalSubmissionDelivery('submission-1', $outcome, $caseId, $currentResult);
    } catch (InvalidArgumentException) {
        continue;
    }
    throw new RuntimeException('Invalid delivery outcome/evidence combination was accepted.');
}
echo "Approval submission outcome and evidence contracts passed.\n";
