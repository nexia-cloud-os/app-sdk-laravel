<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Nexia\Platform\PlatformCallbackWire;
use Nexia\Approval\Resolver\{ResolvedApprovalLine, ResolutionError, ResolutionErrorCode};
use Nexia\Approval\Domain\{ApprovalLineStepDefinition};
use Nexia\Approval\Domain\Enums\ApprovalStepKind;
use Nexia\Process\{ProcessWorkActionResult, ProcessUserTaskSubmissionResult};
use Nexia\Signature\{SignatureDocumentDataResult, SignatureDocumentDataStatus, SignatureDocumentDataResolvedItem, SignatureDocumentDataCandidate};
use Nexia\ResourceReference\ResourceRef;

$ref = new ResourceRef('sample', 'sample.record', '1', 'Synthetic');
$actor = new class implements \Nexia\Identity\Contracts\Actor {
    public function key(): int|string { return 1; }
    public function publicId(): string { return 'actor'; }
    public function displayLabel(): string { return 'Synthetic actor'; }
    public function partyKey(): int|string|null { return 2; }
    public function isVerified(): bool { return true; }
};
$entity = new class implements \Nexia\Organization\Contracts\LegalEntity {
    public function key(): int|string { return 1; }
    public function publicId(): string { return 'entity'; }
    public function displayLabel(): string { return 'Synthetic entity'; }
    public function code(): string { return 'SYNTHETIC'; }
    public function partyPublicId(): ?string { return null; }
    public function isActiveOrganization(): bool { return true; }
};
assert(PlatformCallbackWire::actor($actor)['key'] === $actor->key());
foreach ([
    \Nexia\Approval\Domain\ApprovalException::operationFailed('Private diagnostic.', 'approval_line_required'),
    new \Nexia\Process\Domain\ProcessStartException('Private diagnostic.', 'start_denied'),
    new \Nexia\Process\Domain\ProcessWorkActionException('Private diagnostic.', 'work_failed'),
    new \Nexia\Signature\SignatureException(\Nexia\Signature\SignatureErrorCode::Unauthorized, 'Private diagnostic.'),
] as $failure) {
    $wire = PlatformCallbackWire::error($failure);
    assert(! str_contains(json_encode($wire), 'Private diagnostic.'));
    assert(PlatformCallbackWire::error(PlatformCallbackWire::restoreError($wire)) === $wire);
}
assert(PlatformCallbackWire::error(new RuntimeException('Private diagnostic.')) === null);
$tenant = new class implements \Nexia\Tenancy\Contracts\TenantIdentity {
    public function key(): int|string { return 'synthetic'; }
};
foreach ([
    ['process.work', new \Nexia\Process\ProcessWorkActionInvocation('sample', 'finish', '1', 'process', 1, 'once', $entity, $actor, $ref, [], resourceInputs: ['one' => $ref, 'many' => [$ref]])],
    ['process.user_task', new \Nexia\Process\ProcessUserTaskSubmissionInvocation('sample', 'submit', 'sample.form', '1', 'process', 1, 'task', 'once', $entity, $actor, $ref, [])],
    ['approval.resolve', new \Nexia\Approval\Resolver\ResolverContext($actor, $entity, $ref, subjectActor: $actor)],
    ['signature.data', new \Nexia\Signature\SignatureDocumentDataQuery($tenant, $entity, $actor,
        \Nexia\Signature\SignatureDocumentDataPurpose::SignatureRequestPreparation, 'sample', 'sample.document', 1, $ref,
        ['record' => $ref], $ref, new DateTimeImmutable('2026-09-28T00:00:00+00:00'), ['name'], [$ref])],
] as [$method, $input]) {
    $wire = json_decode(json_encode(PlatformCallbackWire::input($input), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    assert(PlatformCallbackWire::input(PlatformCallbackWire::restoreInput($method, $wire, $actor, $entity, $actor, $tenant)) === $wire);
    $wire['legalEntity']['public_id'] = 'foreign';
    try {
        PlatformCallbackWire::restoreInput($method, $wire, $actor, $entity, $actor, $tenant);
        throw new LogicException('Foreign callback context accepted.');
    } catch (InvalidArgumentException) {}
}
foreach ([
    ['process.work', ProcessWorkActionResult::waitingForApproval(['saved' => true], 'sample.approval', 'case-1')],
    ['process.user_task', new ProcessUserTaskSubmissionResult(['saved' => true])],
    ['approval.resolve', ResolvedApprovalLine::resolved([[new ApprovalLineStepDefinition(ApprovalStepKind::Approval, 1)]])],
    ['approval.resolve', ResolvedApprovalLine::failed([new ResolutionError(ResolutionErrorCode::Unresolved, 'Unavailable')])],
    ['signature.data', new SignatureDocumentDataResult(SignatureDocumentDataStatus::Resolved, resolvedItems: [new SignatureDocumentDataResolvedItem($ref, '1', null, new DateTimeImmutable('2026-09-28T00:00:00+00:00'), ['name' => 'Synthetic'])])],
    ['signature.data', new SignatureDocumentDataResult(SignatureDocumentDataStatus::SelectionRequired, candidates: [new SignatureDocumentDataCandidate($ref, '1', null, new DateTimeImmutable('2026-09-28T00:00:00+00:00'))])],
] as [$method, $result]) {
    $wire = json_decode(json_encode(PlatformCallbackWire::result($result), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    assert(PlatformCallbackWire::result(PlatformCallbackWire::restoreResult($method, $wire)) === $wire);
}
try {
    PlatformCallbackWire::restoreResult('arbitrary.class', []);
    throw new LogicException('Unknown callback accepted.');
} catch (InvalidArgumentException) {}
echo "Closed callback DTO roundtrips passed.\n";

foreach ([new \Nexia\SelfService\SelfWorkContextQuery($actor, null),
    new \Nexia\SelfService\SelfServiceActionQuery($actor, null, $ref)] as $query) {
    $method = $query instanceof \Nexia\SelfService\SelfWorkContextQuery ? 'self.contexts' : 'self.actions';
    $wire = PlatformCallbackWire::input($query);
    assert(PlatformCallbackWire::input(PlatformCallbackWire::restoreInput($method, $wire, $actor, null)) === $wire);
    $wire['actor']['public_id'] = 'foreign';
    try { PlatformCallbackWire::restoreInput($method, $wire, $actor, null); throw new LogicException('Foreign self actor accepted.'); }
    catch (InvalidArgumentException) {}
}
$context = new \Nexia\SelfService\SelfWorkContextOption($ref, $entity, 'Self context');
$action = new \Nexia\SelfService\SelfServiceActionItem('sample.next', 'onboarding', 'Complete details', 'Waiting for you',
    \Nexia\SelfService\SelfServiceActionStage::ActionRequired,
    new \Nexia\SelfService\SelfServiceReturnTarget('employment', workContext: $ref));
assert(PlatformCallbackWire::result([$context])[0]['legalEntity'] === $entity->publicId());
assert(PlatformCallbackWire::result([$action])[0]['returnTarget']['workContext'] === $ref->toArray());
assert(PlatformCallbackWire::restoreResult('self.contexts', PlatformCallbackWire::result([$context]))[0]['resource'] === $ref->toArray());
