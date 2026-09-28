<?php

declare(strict_types=1);

use Nexia\AppDescriptors\DescriptorStatus;
use Nexia\AppDescriptors\SignatureBulkBindingDescriptor;
use Nexia\AppDescriptors\SignatureBulkRoleAssignmentPolicy;
use Nexia\AppDescriptors\SignatureParticipantAssignmentPolicy;
use Nexia\AppDescriptors\SignatureSignatoryRoleDescriptor;
use Nexia\AppDescriptors\SignatureTemplateBindingDescriptor;
use Nexia\AppDescriptors\SignatureTemplateVariableDescriptor;
use Nexia\Events\InboxReplayQuery;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Process\ProcessWorkActionResult;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\Contracts\SignatureHost;
use Nexia\Signature\Contracts\SignatureRequestPreparationHost;
use Nexia\Signature\CurrentBoundSignableDocumentResult;
use Nexia\Signature\CurrentBoundSignableDocumentSubmission;
use Nexia\Signature\PreReadySignableDocumentReference;
use Nexia\Signature\PublishedTemplateSignatureDocumentSource;
use Nexia\Signature\SignableDocumentReference;
use Nexia\Signature\SignableDocumentResolvedField;
use Nexia\Signature\SignableDocumentStatus;
use Nexia\Signature\SignableDocumentSubmissionStatus;
use Nexia\Signature\SignableDocumentSummary;
use Nexia\Signature\SignatureAuthenticationMethod;
use Nexia\Signature\SignatureAuthenticationProfileReference;
use Nexia\Signature\SignatureAuthenticationProfileSnapshot;
use Nexia\Signature\SignatureBulkBindingExecutionContext;
use Nexia\Signature\SignatureBulkBindingExecutionPlan;
use Nexia\Signature\SignatureBulkBindingFailureReason;
use Nexia\Signature\SignatureBulkBindingParticipantFailure;
use Nexia\Signature\SignatureBulkBindingPreviewContext;
use Nexia\Signature\SignatureBulkBindingPreviewResult;
use Nexia\Signature\SignatureBulkBindingPreviewStatus;
use Nexia\Signature\SignatureBulkBindingReauthorizationResult;
use Nexia\Signature\SignatureBulkBindingReauthorizationStatus;
use Nexia\Signature\SignatureBulkGroupSelection;
use Nexia\Signature\SignatureBulkParticipantAssignmentSource;
use Nexia\Signature\SignatureBulkParticipantSetting;
use Nexia\Signature\SignatureBulkRequestedParticipantAssignment;
use Nexia\Signature\SignatureBulkResolvedParticipantAssignment;
use Nexia\Signature\SignatureBulkTemplateParticipantAssignment;
use Nexia\Signature\SignatureBulkTemplateProvenance;
use Nexia\Signature\SignatureCapability;
use Nexia\Signature\SignatureCapabilityKind;
use Nexia\Signature\SignatureCapabilityStatus;
use Nexia\Signature\SignatureCapabilityUnavailableReason;
use Nexia\Signature\SignatureConsentPolicyReference;
use Nexia\Signature\SignatureDataClassification;
use Nexia\Signature\SignatureDocumentAction;
use Nexia\Signature\SignatureDocumentPlanSubmission;
use Nexia\Signature\SignatureDocumentSourceKind;
use Nexia\Signature\SignatureErrorCode;
use Nexia\Signature\SignatureException;
use Nexia\Signature\SignatureExpiryAction;
use Nexia\Signature\SignatureExpiryPolicy;
use Nexia\Signature\SignatureFieldDefinition;
use Nexia\Signature\SignatureFieldKind;
use Nexia\Signature\SignatureFieldRect;
use Nexia\Signature\SignatureInvitationChannel;
use Nexia\Signature\SignatureParticipantAssignmentSource;
use Nexia\Signature\SignatureParticipantProgress;
use Nexia\Signature\SignatureParticipantRoutingState;
use Nexia\Signature\SignatureParticipantSnapshot;
use Nexia\Signature\SignatureParticipantStatus;
use Nexia\Signature\SignatureProcessTerminalResult;
use Nexia\Signature\SignatureProcessWaitDescriptor;
use Nexia\Signature\SignatureRequestAction;
use Nexia\Signature\SignatureRequestActionResult;
use Nexia\Signature\SignatureRequestActionStatus;
use Nexia\Signature\SignatureRequestArtifactReference;
use Nexia\Signature\SignatureRequestCancelInput;
use Nexia\Signature\SignatureRequestOutcome;
use Nexia\Signature\SignatureRequestPasswordVerifier;
use Nexia\Signature\SignatureRequestPreparationReference;
use Nexia\Signature\SignatureRequestPreparationSubmission;
use Nexia\Signature\SignatureRequestQueryInput;
use Nexia\Signature\SignatureRequestReissueInput;
use Nexia\Signature\SignatureRequestResendInput;
use Nexia\Signature\SignatureRequestResult;
use Nexia\Signature\SignatureRequestStatus;
use Nexia\Signature\SignatureRequestSubmission;
use Nexia\Signature\SignatureRequestSubmissionStatus;
use Nexia\Signature\SignatureRequestSummary;
use Nexia\Signature\SignatureRoutingMode;
use Nexia\Signature\SignatureTemplateCatalogEntry;
use Nexia\Signature\SignatureTemplateCatalogResult;
use Nexia\Signature\SignatureTemplateParticipantAssignment;
use Nexia\Signature\SignatureTemplateParticipantAssignmentQuery;
use Nexia\Signature\SignatureTemplateParticipantAssignmentResult;
use Nexia\Signature\SignatureTemplateSourceMode;
use Nexia\Signature\SignatureTemporalDisplayPart;
use Nexia\Signature\SignatureTrustedAssetSelection;
use Nexia\Signature\SignatureTrustedAssetSnapshot;
use Nexia\Signature\SignatureVariableType;
use Nexia\Signature\UploadedPdfSignatureDocumentSource;
use Nexia\Support\CanonicalPayloadFingerprint;
use Nexia\Tenancy\Contracts\TenantIdentity;

require dirname(__DIR__).'/vendor/autoload.php';

/** @param callable(): void $callback */
function signatureContractMustThrow(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException|ValueError) {
        return;
    }

    throw new RuntimeException($message);
}

function signatureContractDescriptor(array $overrides = []): SignatureTemplateBindingDescriptor
{
    $arguments = array_replace([
        'appKey' => 'sample-owner',
        'bindingKey' => 'sample-owner.employment_contract',
        'resourceKey' => 'sample-owner.employment_contract',
        'labelKey' => 'sample-owner.signature_bindings.employment_contract.label',
        'descriptionKey' => 'sample-owner.signature_bindings.employment_contract.description',
        'createPermissionKey' => 'sample-owner.employment_contract.create_document',
        'variables' => [
            new SignatureTemplateVariableDescriptor(
                key: 'worker.full_name',
                labelKey: 'sample-owner.signature_variables.worker.full_name',
                type: SignatureVariableType::String,
                required: true,
                sensitivity: SignatureDataClassification::Confidential,
            ),
            new SignatureTemplateVariableDescriptor(
                key: 'contract.start_date',
                labelKey: 'sample-owner.signature_variables.contract.start_date',
                type: SignatureVariableType::Date,
                required: true,
                sensitivity: SignatureDataClassification::Confidential,
                formatter: 'date.long',
            ),
        ],
        'signatoryRoles' => [
            new SignatureSignatoryRoleDescriptor(
                key: 'worker',
                labelKey: 'sample-owner.signature_bindings.employment_contract.roles.worker',
                minimum: 1,
                maximum: 1,
                assignmentPolicy: new SignatureParticipantAssignmentPolicy([
                    SignatureParticipantAssignmentSource::BindingResolved,
                ]),
            ),
            new SignatureSignatoryRoleDescriptor(
                key: 'employer_representative',
                labelKey: 'sample-owner.signature_bindings.employment_contract.roles.employer_representative',
                minimum: 1,
                maximum: 1,
                assignmentPolicy: new SignatureParticipantAssignmentPolicy([
                    SignatureParticipantAssignmentSource::TemplateFixed,
                    SignatureParticipantAssignmentSource::RequestSupplied,
                ]),
            ),
        ],
        'syntheticSample' => [
            'worker.full_name' => '홍길동',
            'contract.start_date' => '2026-09-01',
        ],
        'templateKeys' => ['sample-owner.employment_contract.new_hire'],
        'version' => '1.0',
        'status' => DescriptorStatus::Active,
        'requestNavigationId' => 'people-employment-contracts',
        'requestNavigationRoute' => '/apps/sample-owner/employment-contracts/new',
    ], $overrides);

    return new SignatureTemplateBindingDescriptor(...$arguments);
}

$descriptor = signatureContractDescriptor();
$roundTrip = SignatureTemplateBindingDescriptor::fromArray($descriptor->toArray());

foreach (SignatureTemplateSourceMode::cases() as $sourceMode) {
    if (SignatureTemplateSourceMode::from($sourceMode->value) !== $sourceMode) {
        throw new RuntimeException('Signature template source modes must round-trip through their serialized value.');
    }
}

$canonicalFlags = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
if (CanonicalPayloadFingerprint::sha256(['b' => 2, 'a' => ['z' => 3, 'y' => 1]], $canonicalFlags)
    !== CanonicalPayloadFingerprint::sha256(['a' => ['y' => 1, 'z' => 3], 'b' => 2], $canonicalFlags)
    || CanonicalPayloadFingerprint::sha256(['value' => 1], $canonicalFlags)
        === CanonicalPayloadFingerprint::sha256(['value' => 1.0], $canonicalFlags)) {
    throw new RuntimeException('Canonical payload fingerprints must ignore object key order without folding numeric types.');
}

if (array_keys(CanonicalPayloadFingerprint::canonicalize(['z' => 1, 'a' => 2])) !== ['a', 'z']
    || CanonicalPayloadFingerprint::json(['z' => 1, 'a' => 2]) !== '{"a":2,"z":1}') {
    throw new RuntimeException('Canonical payload sorting and JSON must be reusable independently of hashing.');
}

if ($roundTrip->toArray() !== $descriptor->toArray()
    || $roundTrip->key !== $descriptor->bindingKey
    || $roundTrip->requestNavigationId !== 'people-employment-contracts'
    || $roundTrip->requestNavigationRoute !== '/apps/sample-owner/employment-contracts/new'
    || $roundTrip->variables[0]->sensitivity !== SignatureDataClassification::Confidential
    || $roundTrip->signatoryRoles[1]->maximum !== 1
    || $roundTrip->signatoryRoles[0]->assignmentPolicy?->allowedSources !== [
        SignatureParticipantAssignmentSource::BindingResolved,
    ]
    || $roundTrip->signatoryRoles[1]->assignmentPolicy?->allowedSources !== [
        SignatureParticipantAssignmentSource::RequestSupplied,
        SignatureParticipantAssignmentSource::TemplateFixed,
    ]) {
    throw new RuntimeException('Signature binding descriptor round-trip changed its immutable schema.');
}

$templateFamily = signatureContractDescriptor([
    'templateKeys' => ['sample-owner.employment_contract.*'],
]);
if (! $templateFamily->acceptsTemplateKey('sample-owner.employment_contract.a')
    || ! $templateFamily->acceptsTemplateKey('sample-owner.employment_contract.b')
    || $templateFamily->acceptsTemplateKey('sample-owner.employment_contract.')
    || $templateFamily->acceptsTemplateKey('sample-owner.offer_letter.a')) {
    throw new RuntimeException('Signature template families must accept only concrete keys below their declared prefix.');
}

$templateCatalog = new SignatureTemplateCatalogResult([
    new SignatureTemplateCatalogEntry('sample-owner.employment_contract.a', 'Employment contract A', null),
    new SignatureTemplateCatalogEntry('sample-owner.employment_contract.b', 'Employment contract B', 'Fixed-term contract'),
]);
if ($templateCatalog->toArray() !== [
    ['template_key' => 'sample-owner.employment_contract.a', 'name' => 'Employment contract A', 'description' => null],
    ['template_key' => 'sample-owner.employment_contract.b', 'name' => 'Employment contract B', 'description' => 'Fixed-term contract'],
]) {
    throw new RuntimeException('Signature template catalogs must preserve selectable template identity and labels.');
}

signatureContractMustThrow(
    static fn () => new SignatureParticipantAssignmentPolicy([]),
    'Signature participant assignment policies must reject an empty source list.',
);
signatureContractMustThrow(
    static fn () => new SignatureParticipantAssignmentPolicy([
        SignatureParticipantAssignmentSource::TemplateFixed,
        SignatureParticipantAssignmentSource::TemplateFixed,
    ]),
    'Signature participant assignment policies must reject duplicate sources.',
);
signatureContractMustThrow(
    static fn () => SignatureParticipantAssignmentPolicy::fromArray([
        'allowed_sources' => ['unsupported'],
    ]),
    'Signature participant assignment policies must reject unknown sources.',
);

$fixedBodyDescriptor = signatureContractDescriptor([
    'appKey' => 'fixed-body',
    'bindingKey' => 'fixed-body.standard_document',
    'resourceKey' => 'shared.record',
    'labelKey' => 'fixed-body.signature_bindings.standard_document.label',
    'descriptionKey' => 'fixed-body.signature_bindings.standard_document.description',
    'createPermissionKey' => 'fixed-body.document.create',
    'variables' => [],
    'signatoryRoles' => [new SignatureSignatoryRoleDescriptor(
        key: 'signer',
        labelKey: 'fixed-body.signature_bindings.standard_document.roles.signer',
        minimum: 1,
        maximum: 1,
    )],
    'syntheticSample' => [],
    'templateKeys' => ['fixed-body.standard_document'],
]);

if ($fixedBodyDescriptor->variables !== []
    || $fixedBodyDescriptor->resourceKey !== 'shared.record') {
    throw new RuntimeException('Fixed-body bindings must allow zero variables and a separately owned resource key.');
}

signatureContractMustThrow(
    static fn () => signatureContractDescriptor(['bindingKey' => 'shared.document']),
    'Signature binding descriptors must retain declaring-App ownership for binding keys.',
);

signatureContractMustThrow(
    static fn () => signatureContractDescriptor(['templateKeys' => ['shared.document']]),
    'Signature binding descriptors must retain declaring-App ownership for template keys.',
);

signatureContractMustThrow(
    static fn () => signatureContractDescriptor(['requestNavigationId' => ' people-employment-contracts']),
    'Signature binding descriptors must reject non-normalized request navigation ids.',
);

signatureContractMustThrow(
    static fn () => signatureContractDescriptor([
        'requestNavigationId' => null,
        'requestNavigationRoute' => '/apps/sample-owner/employment-contracts/new',
    ]),
    'Signature binding request routes must retain a permission-visible navigation destination.',
);

signatureContractMustThrow(
    static fn () => signatureContractDescriptor(['requestNavigationRoute' => 'https://example.test/contracts/new']),
    'Signature binding request routes must stay inside the local Shell.',
);

signatureContractMustThrow(
    static fn () => signatureContractDescriptor(['syntheticSample' => [
        'worker.full_name' => '홍길동',
        'contract.start_date' => '2026-09-01',
        'worker.unknown' => 'not allowed',
    ]]),
    'Signature binding descriptors must reject unknown synthetic variables.',
);

$workerRole = $descriptor->signatoryRoles[0];
signatureContractMustThrow(
    static fn () => signatureContractDescriptor(['signatoryRoles' => [$workerRole, $workerRole]]),
    'Signature binding descriptors must reject duplicate signatory roles.',
);

signatureContractMustThrow(
    static fn () => new SignatureSignatoryRoleDescriptor(
        key: 'worker',
        labelKey: 'sample-owner.signature_bindings.employment_contract.roles.worker',
        minimum: 2,
        maximum: 1,
    ),
    'Signature signatory roles must reject invalid cardinality.',
);

$field = new SignatureFieldDefinition(
    key: 'worker.signature',
    signatoryRoleKey: 'worker',
    kind: SignatureFieldKind::Signature,
    rect: new SignatureFieldRect(page: 1, x: 0.1, y: 0.75, width: 0.3, height: 0.15),
);

if (SignatureFieldDefinition::fromArray($field->toArray())->toArray() !== $field->toArray()) {
    throw new RuntimeException('Normalized signature field geometry must round-trip without loss.');
}

$slottedField = new SignatureFieldDefinition(
    key: 'signer_2.signature',
    signatoryRoleKey: 'signer',
    kind: SignatureFieldKind::Signature,
    rect: new SignatureFieldRect(page: 1, x: 0.1, y: 0.75, width: 0.3, height: 0.15),
    participantSlot: 2,
);
$configuredField = new SignatureFieldDefinition(
    key: 'signer.capacity',
    signatoryRoleKey: 'signer',
    kind: SignatureFieldKind::Select,
    rect: new SignatureFieldRect(page: 1, x: 0.45, y: 0.75, width: 0.3, height: 0.04),
    required: false,
    participantSlot: 2,
    label: 'Signing capacity',
    inputGuide: 'Select the capacity used for signing.',
    defaultValue: 'Director',
    options: ['Director', 'Representative'],
);
$dateField = new SignatureFieldDefinition(
    key: 'signer.signed_on',
    signatoryRoleKey: 'signer',
    kind: SignatureFieldKind::Date,
    rect: new SignatureFieldRect(page: 1, x: 0.1, y: 0.85, width: 0.2, height: 0.04),
    datePart: SignatureTemporalDisplayPart::Year,
);
$signedAtField = new SignatureFieldDefinition(
    key: 'signer.signed_at',
    signatoryRoleKey: 'signer',
    kind: SignatureFieldKind::SignedAt,
    rect: new SignatureFieldRect(page: 1, x: 0.35, y: 0.85, width: 0.2, height: 0.04),
    datePart: SignatureTemporalDisplayPart::Time,
);
if (SignatureFieldDefinition::fromArray($slottedField->toArray())->toArray() !== $slottedField->toArray()
    || ($field->toArray()['participant_slot'] ?? null) !== null
    || array_key_exists('participant_slot', $field->toArray())
    || SignatureFieldDefinition::fromArray($configuredField->toArray())->toArray() !== $configuredField->toArray()
    || SignatureFieldDefinition::fromArray($dateField->toArray())->toArray() !== $dateField->toArray()
    || SignatureFieldDefinition::fromArray($signedAtField->toArray())->toArray() !== $signedAtField->toArray()) {
    throw new RuntimeException('Signature field participant slots must round-trip without changing legacy serialization.');
}

signatureContractMustThrow(
    static fn () => new SignatureFieldDefinition(
        key: 'signer.invalid_date',
        signatoryRoleKey: 'signer',
        kind: SignatureFieldKind::Date,
        rect: new SignatureFieldRect(page: 1, x: 0.1, y: 0.9, width: 0.2, height: 0.04),
        datePart: SignatureTemporalDisplayPart::Time,
    ),
    'Date-only signature fields must reject a time-only display part.',
);

signatureContractMustThrow(
    static fn () => new SignatureFieldRect(page: 1, x: 0.9, y: 0.9, width: 0.2, height: 0.2),
    'Normalized signature field geometry must stay within page bounds.',
);

$profile = new SignatureAuthenticationProfileSnapshot(
    profileKey: 'standard_password_email_otp',
    version: '1.0',
    requiredMethods: [
        SignatureAuthenticationMethod::RequestPassword,
        SignatureAuthenticationMethod::EmailOtp,
    ],
);

if (SignatureAuthenticationProfileSnapshot::fromArray($profile->toArray())->toArray() !== $profile->toArray()) {
    throw new RuntimeException('Authentication profile snapshots must preserve exact required methods and version.');
}

signatureContractMustThrow(
    static fn () => new SignatureAuthenticationProfileSnapshot(
        profileKey: 'invalid',
        version: '1.0',
        requiredMethods: [],
    ),
    'Authentication profile snapshots must reject an empty required-method list.',
);

$email = new SignatureCapability(
    kind: SignatureCapabilityKind::InvitationChannel,
    key: 'email',
    status: SignatureCapabilityStatus::Operational,
    labelKey: 'signature.invitation.channel.email',
    descriptionKey: 'signature.invitation.channel.email.description',
);
$sms = new SignatureCapability(
    kind: SignatureCapabilityKind::InvitationChannel,
    key: 'sms',
    status: SignatureCapabilityStatus::Unavailable,
    labelKey: 'signature.invitation.channel.sms',
    descriptionKey: 'signature.invitation.channel.sms.description',
    unavailableReason: SignatureCapabilityUnavailableReason::SmsTransportUnconfigured,
);
$parallelRouting = new SignatureCapability(
    kind: SignatureCapabilityKind::RoutingMode,
    key: SignatureRoutingMode::Parallel->value,
    status: SignatureCapabilityStatus::Operational,
    labelKey: 'signature.routing_mode.parallel',
    descriptionKey: 'signature.routing_mode.parallel.description',
);
$sequentialRouting = new SignatureCapability(
    kind: SignatureCapabilityKind::RoutingMode,
    key: SignatureRoutingMode::Sequential->value,
    status: SignatureCapabilityStatus::Unavailable,
    labelKey: 'signature.routing_mode.sequential',
    descriptionKey: 'signature.routing_mode.sequential.description',
    unavailableReason: SignatureCapabilityUnavailableReason::RoutingModeUnavailable,
);

if (SignatureCapability::fromArray($email->toArray())->status !== SignatureCapabilityStatus::Operational
    || SignatureCapability::fromArray($sms->toArray())->unavailableReason !== SignatureCapabilityUnavailableReason::SmsTransportUnconfigured
    || SignatureCapability::fromArray($parallelRouting->toArray())->toArray() !== $parallelRouting->toArray()
    || SignatureCapability::fromArray($sequentialRouting->toArray())->toArray() !== $sequentialRouting->toArray()) {
    throw new RuntimeException('Signature capability availability must round-trip without hiding unavailable options.');
}

if (SignatureCapabilityUnavailableReason::LocalDeliveryUnavailable->value !== 'local_delivery_unavailable'
    || SignatureCapabilityUnavailableReason::LocalAuthenticationUnavailable->value !== 'local_authentication_unavailable') {
    throw new RuntimeException('Local signature dependency failures must retain stable app-neutral reason codes.');
}

$inboxReplay = new InboxReplayQuery(
    consumerKey: 'sample-owner.employment_contract.signature_outcome.v1',
    eventName: 'signature.request.outcome.v1',
    aggregateType: 'signature.request',
    aggregateId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
);
if ($inboxReplay->consumerKey !== 'sample-owner.employment_contract.signature_outcome.v1'
    || $inboxReplay->aggregateId !== '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8') {
    throw new RuntimeException('Inbox replay lookup identity must preserve the exact consumer and aggregate boundary.');
}
signatureContractMustThrow(
    static fn () => new InboxReplayQuery(' consumer', 'signature.request.outcome.v1', 'signature.request', 'request'),
    'Inbox replay lookup identity must reject whitespace-normalized values.',
);
signatureContractMustThrow(
    static fn () => new InboxReplayQuery('consumer', 'signature.request.outcome.v1', 'signature.request', str_repeat('a', 192)),
    'Inbox replay lookup identity must remain bounded for durable storage.',
);

signatureContractMustThrow(
    static fn () => new SignatureCapability(
        kind: SignatureCapabilityKind::InvitationChannel,
        key: 'sms',
        status: SignatureCapabilityStatus::Unavailable,
        labelKey: 'signature.invitation.channel.sms',
        descriptionKey: 'signature.invitation.channel.sms.description',
    ),
    'Unavailable signature capabilities must carry a stable reason code.',
);

if (array_values(array_intersect(
    array_column(SignatureDocumentAction::cases(), 'value'),
    array_column(SignatureRequestAction::cases(), 'value'),
)) !== ['cancel']) {
    throw new RuntimeException('Document preparation and request dispatch action contracts must stay separate.');
}

$preReadyReference = new PreReadySignableDocumentReference(
    publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
    revision: 1,
);
if ($preReadyReference->revision !== 1
    || SignatureDocumentAction::RebuildPreReady->value !== 'rebuild_pre_ready') {
    throw new RuntimeException('Pre-ready rebuild must remain an explicit document-only SDK action.');
}
signatureContractMustThrow(
    static fn () => new PreReadySignableDocumentReference('', 0),
    'Pre-ready rebuild references must reject blank identities and non-positive revisions.',
);

$documentResult = new CurrentBoundSignableDocumentResult(
    publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
    submissionStatus: SignableDocumentSubmissionStatus::Accepted,
    documentStatus: SignableDocumentStatus::Queued,
    bindingKey: 'sample-owner.employment_contract',
    bindingVersion: '1.0',
    templateKey: 'sample-owner.employment_contract.new_hire',
    templateVersion: 2,
    revision: 1,
);

if ($documentResult->documentStatus !== SignableDocumentStatus::Queued
    || $documentResult->templateVersion !== 2
    || count(SignableDocumentStatus::cases()) !== 7) {
    throw new RuntimeException('Signable document submission results must freeze the resolved identity and lifecycle.');
}

$documentSummary = new SignableDocumentSummary(
    publicId: $documentResult->publicId,
    status: SignableDocumentStatus::Ready,
    revision: 1,
    bindingKey: $documentResult->bindingKey,
    bindingVersion: $documentResult->bindingVersion,
    templateKey: $documentResult->templateKey,
    templateVersion: $documentResult->templateVersion,
    sourcePageCount: 2,
    resolvedFields: [
        new SignableDocumentResolvedField('worker_signature', 'worker', true),
        new SignableDocumentResolvedField('employer_signature', 'employer_representative', true),
    ],
    checksum: str_repeat('a', 64),
    failureCode: null,
    renderAttempts: 1,
    trustedAssets: [new SignatureTrustedAssetSnapshot(
        kind: 'official_seal',
        publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d7',
        version: 2,
        sourceChecksum: str_repeat('b', 64),
        usagePublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
        outputChecksum: str_repeat('a', 64),
        placementKey: 'company.execution_seal',
    )],
);
if ($documentSummary->sourcePageCount !== 2
    || count($documentSummary->resolvedFields) !== 2
    || $documentSummary->resolvedFields[0]->signatoryRoleKey !== 'worker'
    || SignatureTrustedAssetSnapshot::fromArray($documentSummary->trustedAssets[0]->toArray())->toArray()
        !== $documentSummary->trustedAssets[0]->toArray()) {
    throw new RuntimeException('Authorized document summaries must freeze page count and PII-free resolved fields.');
}
signatureContractMustThrow(
    static fn () => new SignableDocumentSummary(
        publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
        status: SignableDocumentStatus::Ready,
        revision: 1,
        bindingKey: 'sample-owner.employment_contract',
        bindingVersion: '1.0',
        templateKey: 'sample-owner.employment_contract.new_hire',
        templateVersion: 2,
        sourcePageCount: 0,
        resolvedFields: [],
        checksum: str_repeat('a', 64),
        failureCode: null,
        renderAttempts: 1,
    ),
    'Document summaries must reject a missing source page count.',
);

$signatureContractLegalEntity = new class implements LegalEntity
{
    public function key(): int|string
    {
        return 1;
    }

    public function publicId(): string
    {
        return '018f43d0-9ab6-7e1b-8e8a-18f5089db3d1';
    }

    public function displayLabel(): string
    {
        return 'Nexia Korea';
    }

    public function code(): string
    {
        return 'NEXIA-KR';
    }

    public function partyPublicId(): ?string
    {
        return '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2';
    }

    public function isActiveOrganization(): bool
    {
        return true;
    }
};

$signatureContractActor = new class implements Actor
{
    public function key(): int|string
    {
        return 101;
    }

    public function publicId(): string
    {
        return '018f43d0-9ab6-7e1b-8e8a-18f5089db3d3';
    }

    public function displayLabel(): string
    {
        return 'Signature Coordinator';
    }

    public function partyKey(): int|string|null
    {
        return 201;
    }

    public function isVerified(): bool
    {
        return true;
    }
};

$signatureRequestSubject = new ResourceRef(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.employment_contract',
    resourceId: 'employment-contract-0001',
    display: 'Employment Contract 0001',
);
$templateAssignments = [
    new SignatureTemplateParticipantAssignment(
        roleKey: 'worker',
        participantSlot: 1,
        source: SignatureParticipantAssignmentSource::BindingResolved,
    ),
    new SignatureTemplateParticipantAssignment(
        roleKey: 'employer_representative',
        participantSlot: 1,
        source: SignatureParticipantAssignmentSource::TemplateFixed,
        fixedPartyPublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2',
    ),
];
$templateAssignmentResult = new SignatureTemplateParticipantAssignmentResult(
    templatePublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d4',
    templateVersion: 2,
    contentHash: str_repeat('a', 64),
    assignments: $templateAssignments,
);
$templateAssignmentQuery = new SignatureTemplateParticipantAssignmentQuery(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    bindingKey: 'sample-owner.employment_contract',
    bindingVersion: '1.0',
    templateKey: 'sample-owner.employment_contract.new_hire',
    locale: 'ko-KR',
    effectiveOn: '2026-08-19',
);
if ($templateAssignmentQuery->subject !== $signatureRequestSubject
    || $templateAssignmentResult->toArray()['assignments'] !== array_map(
        static fn (SignatureTemplateParticipantAssignment $assignment): array => $assignment->toArray(),
        $templateAssignments,
    )
    || SignatureTemplateParticipantAssignment::fromArray($templateAssignments[1]->toArray())->toArray()
        !== $templateAssignments[1]->toArray()) {
    throw new RuntimeException('Published template participant assignment contracts must preserve exact contact-free identities.');
}
signatureContractMustThrow(
    static fn () => SignatureTemplateParticipantAssignment::fromArray([
        'role_key' => 'worker',
        'participant_slot' => 1,
        'assignment_source' => 'binding_resolved',
        'fixed_party_public_id' => 123,
    ]),
    'Signature template participant assignments must reject non-string fixed Party identifiers.',
);
signatureContractMustThrow(
    static fn () => new SignatureTemplateParticipantAssignmentResult(
        templatePublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d4',
        templateVersion: 2,
        contentHash: str_repeat('a', 64),
        assignments: [$templateAssignments[0], $templateAssignments[0]],
    ),
    'Signature template participant assignment results must reject duplicate role and slot identities.',
);
$preparationSubmission = new SignatureRequestPreparationSubmission(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    bindingKey: 'sample-owner.employment_contract',
    bindingVersion: '1.0',
    templateKey: 'sample-owner.employment_contract.new_hire',
    locale: 'ko-KR',
    effectiveOn: '2026-08-19',
    canonicalVariables: [
        'contract.start_date' => '2026-09-01',
        'worker.full_name' => '홍길동',
    ],
    participantDraftSnapshot: [[
        'role_key' => 'worker',
        'participant_slot' => 1,
        'party_public_id' => '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2',
    ]],
);
$preparationHost = new class implements SignatureRequestPreparationHost
{
    public function begin(SignatureRequestPreparationSubmission $submission): SignatureRequestPreparationReference
    {
        return new SignatureRequestPreparationReference(
            publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d9',
            revision: 1,
        );
    }

    public function submit(
        SignatureRequestPreparationReference $preparation,
        SignatureRequestSubmission $submission,
    ): SignatureRequestResult {
        throw new RuntimeException('Preparation host test double does not submit requests.');
    }
};
$preparationReference = $preparationHost->begin($preparationSubmission);
if ($preparationReference->revision !== 1
    || $preparationSubmission->canonicalVariables['worker.full_name'] !== '홍길동'
    || $preparationSubmission->participantDraftSnapshot[0]['role_key'] !== 'worker') {
    throw new RuntimeException('Request preparation contracts must retain App-authorized canonical and participant snapshots.');
}
new SignatureRequestPreparationSubmission(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    bindingKey: 'sample-owner.fixed_body',
    bindingVersion: '1.0',
    templateKey: 'sample-owner.fixed_body.standard',
    locale: 'en',
    effectiveOn: '2026-08-19',
    canonicalVariables: [],
    participantDraftSnapshot: [],
);
signatureContractMustThrow(
    static fn () => new SignatureRequestPreparationSubmission(
        legalEntity: $signatureContractLegalEntity,
        actor: $signatureContractActor,
        subject: $signatureRequestSubject,
        bindingKey: 'sample-owner.employment_contract',
        bindingVersion: '1.0',
        templateKey: 'sample-owner.employment_contract.new_hire',
        locale: 'ko-KR',
        effectiveOn: '2026-08-19',
        canonicalVariables: ['list-value'],
        participantDraftSnapshot: [],
    ),
    'Request preparation canonical variables must be a keyed snapshot.',
);
signatureContractMustThrow(
    static fn () => new SignatureRequestPreparationReference(' ', 0),
    'Request preparation references must reject invalid identities and revisions.',
);
$fixedBodySubmission = new CurrentBoundSignableDocumentSubmission(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: new ResourceRef(
        appKey: 'directory',
        resourceKey: 'directory.party',
        resourceId: 'generic-party-document-0001',
        display: 'Generic Party Document 0001',
    ),
    bindingKey: 'signature.generic_party_document',
    bindingVersion: '1.0',
    templateKey: 'signature.generic.template-00000000-0000-4000-8000-000000000001',
    locale: 'ko-KR',
    effectiveOn: '2026-08-19',
    variables: [],
    signatoryRoles: ['signer' => [['party_public_id' => '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2']],
    ],
    idempotencyKey: 'fixed-body-generic-document-0001',
    correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d4',
    trustedAssets: [new SignatureTrustedAssetSelection(
        kind: 'official_seal',
        publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d7',
        placementKey: 'company.execution_seal',
    )],
);
if ($fixedBodySubmission->variables !== []
    || SignatureTrustedAssetSelection::fromArray($fixedBodySubmission->trustedAssets[0]->toArray())->toArray()
        !== $fixedBodySubmission->trustedAssets[0]->toArray()) {
    throw new RuntimeException('Fixed-body signature bindings must permit an empty variable snapshot.');
}
$signatureRequestDocument = new SignableDocumentReference(
    publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
    revision: 1,
    checksum: str_repeat('a', 64),
);

if (SignableDocumentReference::fromArray($signatureRequestDocument->toArray())->toArray() !== $signatureRequestDocument->toArray()) {
    throw new RuntimeException('Ready signable document references must retain public id, revision, and checksum on round-trip.');
}

$authenticationProfileReference = new SignatureAuthenticationProfileReference(
    profileKey: 'standard_password_email_otp',
    version: '1.0',
);
$consentPolicyReference = new SignatureConsentPolicyReference(
    policyKey: 'standard_employment_contract_consent',
    version: '2026.1',
);
$expiryPolicy = new SignatureExpiryPolicy(
    expiresAt: '2026-09-30T23:59:59+00:00',
    onExpiry: SignatureExpiryAction::Expire,
);

if (SignatureAuthenticationProfileReference::fromArray($authenticationProfileReference->toArray())->toArray() !== $authenticationProfileReference->toArray()
    || $profile->reference()->toArray() !== $authenticationProfileReference->toArray()
    || SignatureConsentPolicyReference::fromArray($consentPolicyReference->toArray())->toArray() !== $consentPolicyReference->toArray()
    || SignatureExpiryPolicy::fromArray($expiryPolicy->toArray())->toArray() !== $expiryPolicy->toArray()) {
    throw new RuntimeException('Signature request policy references and expiry policy must preserve exact key and version snapshots.');
}

$signatureParticipant = new SignatureParticipantSnapshot(
    roleKey: 'worker',
    sequence: 1,
    assignedFieldKeys: ['worker.signer_name', 'worker.signature', 'worker.signed_at'],
    displayName: 'Worker Sample',
    locale: 'ko-KR',
    invitationChannel: SignatureInvitationChannel::Email,
    invitationAddress: 'worker@example.test',
    authenticationProfile: $authenticationProfileReference,
    requiredMethods: [
        SignatureAuthenticationMethod::RequestPassword,
        SignatureAuthenticationMethod::EmailOtp,
    ],
    emailOtpChallengeAddress: 'worker+otp@example.test',
    partyPublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2',
);

$publishedPlan = new SignatureDocumentPlanSubmission(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    bindingKey: 'sample-owner.employment_contract',
    bindingVersion: '1.0',
    locale: 'ko',
    effectiveOn: '2026-08-19',
    source: new PublishedTemplateSignatureDocumentSource(
        templateKey: 'sample-owner.employment_contract.new_hire',
        variables: ['worker.full_name' => 'Alex Kim'],
        signatoryRoles: ['worker' => [['party_public_id' => '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2']]],
    ),
    trustedAssets: [],
    idempotencyKey: 'employment-contract-plan-0001',
    correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d5',
);
$uploadedPlan = new SignatureDocumentPlanSubmission(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    bindingKey: 'sample-owner.employment_contract',
    bindingVersion: '1.0',
    locale: 'ko',
    effectiveOn: '2026-08-19',
    source: new UploadedPdfSignatureDocumentSource(
        uploadIntentId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d6',
        participants: [$signatureParticipant],
        fields: [$field],
    ),
    trustedAssets: [new SignatureTrustedAssetSelection(
        kind: 'official_seal',
        publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d7',
        placementKey: 'company.execution_seal',
    )],
    idempotencyKey: 'employment-contract-plan-0002',
    correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d9',
);
if ($publishedPlan->source->kind() !== SignatureDocumentSourceKind::PublishedTemplate
    || $uploadedPlan->source->kind() !== SignatureDocumentSourceKind::UploadedPdf
    || $uploadedPlan->trustedAssets[0]->kind !== 'official_seal') {
    throw new RuntimeException('Signature document plans must compose source, participants, and trusted assets without App-domain modes.');
}
signatureContractMustThrow(
    static fn () => new SignatureDocumentPlanSubmission(
        legalEntity: $signatureContractLegalEntity,
        actor: $signatureContractActor,
        subject: $signatureRequestSubject,
        bindingKey: 'sample-owner.employment_contract',
        bindingVersion: '1.0',
        locale: 'ko',
        effectiveOn: '2026-08-19',
        source: $publishedPlan->source,
        trustedAssets: [
            new SignatureTrustedAssetSelection('official_seal', '018f43d0-9ab6-7e1b-8e8a-18f5089db3d7', 'company.execution_seal'),
            new SignatureTrustedAssetSelection('official_seal', '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8', 'company.execution_seal'),
        ],
        idempotencyKey: 'employment-contract-plan-duplicate-placement',
        correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d9',
    ),
    'Signature document plans must reject duplicate trusted-asset placements.',
);
signatureContractMustThrow(
    static fn () => new SignatureTrustedAssetSnapshot(
        kind: 'official_seal',
        publicId: 'not-a-public-id',
        version: 1,
        sourceChecksum: str_repeat('a', 64),
        usagePublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d8',
        outputChecksum: str_repeat('b', 64),
        placementKey: 'company.execution_seal',
    ),
    'Trusted-asset provenance must retain canonical public identities.',
);

$opaqueVerifier = SignatureRequestPasswordVerifier::fromHandoffValue(
    password_hash('sdk-only-request-password', PASSWORD_ARGON2ID) ?: throw new RuntimeException('Argon2id is unavailable.'),
);
$signatureParticipantWithVerifier = new SignatureParticipantSnapshot(
    roleKey: 'worker',
    sequence: 1,
    assignedFieldKeys: ['worker.signer_name', 'worker.signature', 'worker.signed_at'],
    displayName: 'Worker Sample',
    locale: 'ko-KR',
    invitationChannel: SignatureInvitationChannel::Email,
    invitationAddress: 'worker@example.test',
    authenticationProfile: $authenticationProfileReference,
    requiredMethods: [SignatureAuthenticationMethod::RequestPassword, SignatureAuthenticationMethod::EmailOtp],
    emailOtpChallengeAddress: 'worker+otp@example.test',
    requestPasswordVerifier: $opaqueVerifier,
);
if (array_key_exists('request_password_verifier', $signatureParticipantWithVerifier->toArray())
    || array_key_exists('request_password_verifier', $signatureParticipantWithVerifier->toLogSafeArray())) {
    throw new RuntimeException('Request password verifier material must not enter SDK transport or log-safe snapshots.');
}

if (($signatureParticipant->toArray()['party_public_id'] ?? null) !== '018f43d0-9ab6-7e1b-8e8a-18f5089db3d2'
    || array_key_exists('party_public_id', $signatureParticipant->toLogSafeArray())) {
    throw new RuntimeException('Signature participant Party identity must be transported but excluded from log-safe snapshots.');
}

if (SignatureParticipantSnapshot::fromArray($signatureParticipant->toArray())->toArray() !== $signatureParticipant->toArray()) {
    throw new RuntimeException('Signature participant snapshots must preserve assignment, invitation, and authentication policy values.');
}

signatureContractMustThrow(
    static fn () => new SignatureParticipantSnapshot(
        roleKey: 'worker',
        sequence: 1,
        assignedFieldKeys: [],
        displayName: 'Worker Sample',
        locale: 'ko-KR',
        invitationChannel: SignatureInvitationChannel::Email,
        invitationAddress: 'worker@example.test',
        authenticationProfile: new SignatureAuthenticationProfileReference('standard_password', '1.0'),
        requiredMethods: [SignatureAuthenticationMethod::EmailOtp],
    ),
    'Email OTP participant snapshots must provide their selected challenge address.',
);

signatureContractMustThrow(
    static fn () => new SignatureParticipantSnapshot(
        roleKey: 'worker',
        sequence: 1,
        assignedFieldKeys: [],
        displayName: 'Worker Sample',
        locale: 'ko-KR',
        invitationChannel: SignatureInvitationChannel::Email,
        invitationAddress: 'worker@example.test',
        authenticationProfile: new SignatureAuthenticationProfileReference('standard_email_otp', '1.0'),
        requiredMethods: [SignatureAuthenticationMethod::EmailOtp],
        emailOtpChallengeAddress: 'worker@example.test',
        partyPublicId: 'NOT-A-CANONICAL-UUID',
    ),
    'Signature participant Party identity must use a canonical UUID.',
);

$signatureRequestSubmission = new SignatureRequestSubmission(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    expectedDocument: $signatureRequestDocument,
    participants: [$signatureParticipant],
    consentPolicy: $consentPolicyReference,
    expiryPolicy: $expiryPolicy,
    idempotencyKey: 'signature-handoff:employment-contract-0001',
    correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d4',
    causationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d5',
);
$signatureRequestSubmissionArray = $signatureRequestSubmission->toArray();

$credentialSubmission = new SignatureRequestSubmission(...[
    ...get_object_vars($signatureRequestSubmission), 'participants' => [$signatureParticipantWithVerifier],
]);
$credentialWire = \Nexia\Signature\SignatureRequestWire::submission($credentialSubmission);
$credentialRestored = \Nexia\Signature\SignatureRequestWire::restoreSubmission(
    json_decode(json_encode($credentialWire, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
    $signatureContractLegalEntity, $signatureContractActor,
);
if ($credentialRestored->participants[0]->requestPasswordVerifier?->valueForSignatureHost() !== $opaqueVerifier->valueForSignatureHost()
    || str_contains(json_encode($credentialRestored->toArray(), JSON_THROW_ON_ERROR), $opaqueVerifier->valueForSignatureHost())
    || str_contains(json_encode($credentialRestored->toLogSafeArray(), JSON_THROW_ON_ERROR), $opaqueVerifier->valueForSignatureHost())) {
    throw new RuntimeException('Only authenticated host transport may carry the password verifier.');
}
foreach ([['foreign' => 'opaque'], [1 => null], [1 => str_repeat('x', 1025)]] as $invalidVerifiers) {
    signatureContractMustThrow(fn () => \Nexia\Signature\SignatureRequestWire::restoreSubmission(
        [...$credentialWire, 'request_password_verifiers' => $invalidVerifiers], $signatureContractLegalEntity, $signatureContractActor,
    ), 'Credential handoff must reject unknown participants and malformed material.');
}

if (array_keys($signatureRequestSubmissionArray) !== [
    'legal_entity_public_id',
    'actor_public_id',
    'subject',
    'expected_document',
    'participants',
    'consent_policy',
    'expiry_policy',
    'idempotency_key',
    'correlation_id',
    'causation_id',
]
    || SignatureRequestSubmission::fromArray(
        $signatureRequestSubmissionArray,
        $signatureContractLegalEntity,
        $signatureContractActor,
    )->toArray() !== $signatureRequestSubmissionArray) {
    throw new RuntimeException('Signature request submissions must round-trip their immutable document, policy, context, and idempotency contract.');
}

if (array_key_exists('routing_mode', $signatureRequestSubmissionArray)
    || array_key_exists('signing_order', $signatureRequestSubmissionArray)) {
    throw new RuntimeException('Signature request submissions must not allow callers to override template routing policy.');
}

$signatureRequestResult = new SignatureRequestResult(
    publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d6',
    submissionStatus: SignatureRequestSubmissionStatus::Accepted,
    status: SignatureRequestStatus::Preparing,
    subject: $signatureRequestSubject,
    document: $signatureRequestDocument,
);

if (SignatureRequestResult::fromArray($signatureRequestResult->toArray())->toArray() !== $signatureRequestResult->toArray()
    || $signatureRequestResult->routingMode !== SignatureRoutingMode::Parallel
    || str_contains(json_encode($signatureRequestResult->toArray(), JSON_THROW_ON_ERROR), 'recipient_')
    || str_contains(json_encode($signatureRequestResult->toArray(), JSON_THROW_ON_ERROR), 'pdf_url')) {
    throw new RuntimeException('Signature request results must round-trip their public document and subject references.');
}

$sequentialRequestResult = new SignatureRequestResult(
    publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3f0',
    submissionStatus: SignatureRequestSubmissionStatus::Existing,
    status: SignatureRequestStatus::InProgress,
    subject: $signatureRequestSubject,
    document: $signatureRequestDocument,
    routingMode: SignatureRoutingMode::Sequential,
);
$legacyRequestResult = $signatureRequestResult->toArray();
unset($legacyRequestResult['routing_mode']);
if (SignatureRequestResult::fromArray($sequentialRequestResult->toArray())->toArray() !== $sequentialRequestResult->toArray()
    || SignatureRequestResult::fromArray($legacyRequestResult)->routingMode !== SignatureRoutingMode::Parallel) {
    throw new RuntimeException('Signature request results must preserve sequential routing and default legacy payloads to parallel.');
}

try {
    SignatureRoutingMode::from('not-a-routing-mode');
    throw new RuntimeException('Invalid signature routing modes must fail restoration.');
} catch (ValueError) {
    // Expected enum restoration failure.
}

$signatureRequestSummary = new SignatureRequestSummary(
    publicId: $signatureRequestResult->publicId,
    status: SignatureRequestStatus::InProgress,
    subject: $signatureRequestSubject,
    document: $signatureRequestDocument,
    expiresAt: $expiryPolicy->expiresAt,
    participants: [
        new SignatureParticipantProgress(
            roleKey: 'worker',
            sequence: 1,
            status: SignatureParticipantStatus::Signed,
            completedAt: '2026-09-02T10:00:00+00:00',
            roleSlot: 1,
            routingState: SignatureParticipantRoutingState::Completed,
            presentedRevision: 0,
        ),
        new SignatureParticipantProgress(
            roleKey: 'employer_representative',
            sequence: 2,
            status: SignatureParticipantStatus::InProgress,
            roleSlot: 1,
            routingState: SignatureParticipantRoutingState::Active,
            presentedRevision: 1,
        ),
    ],
    predecessorPublicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d7',
    routingMode: SignatureRoutingMode::Sequential,
);

if (SignatureRequestSummary::fromArray($signatureRequestSummary->toArray())->toArray() !== $signatureRequestSummary->toArray()
    || array_intersect(
        ['recipient_name', 'recipient_address', 'invitation_address', 'media_id', 'pdf_url', 'signature_stroke'],
        array_keys($signatureRequestSummary->toLogSafeArray()),
    ) !== []
    || str_contains(json_encode($signatureRequestSummary->toLogSafeArray(), JSON_THROW_ON_ERROR), 'recipient_')
    || str_contains(json_encode($signatureRequestSummary->toLogSafeArray(), JSON_THROW_ON_ERROR), 'pdf_url')
    || str_contains(json_encode($signatureRequestSummary->toLogSafeArray(), JSON_THROW_ON_ERROR), 'media_id')
    || str_contains(json_encode($signatureRequestSummary->toLogSafeArray(), JSON_THROW_ON_ERROR), 'signature_stroke')) {
    throw new RuntimeException('Log-safe signature request summaries must round-trip without participant contact data.');
}

$routingStates = [
    SignatureParticipantRoutingState::Waiting,
    SignatureParticipantRoutingState::RevisionPending,
    SignatureParticipantRoutingState::Active,
    SignatureParticipantRoutingState::Completed,
    SignatureParticipantRoutingState::Closed,
    SignatureParticipantRoutingState::Failed,
];
foreach ($routingStates as $index => $routingState) {
    $progress = new SignatureParticipantProgress(
        roleKey: 'generic_party',
        sequence: $index + 1,
        status: SignatureParticipantStatus::Pending,
        roleSlot: $index + 1,
        routingState: $routingState,
        presentedRevision: $index,
    );
    if (SignatureParticipantProgress::fromArray($progress->toArray())->toArray() !== $progress->toArray()) {
        throw new RuntimeException('Every participant routing state must round-trip with role slot and presented revision.');
    }
}

$legacySummary = $signatureRequestSummary->toArray();
unset($legacySummary['routing_mode']);
$legacySummary['participants'] = [
    ['role_key' => 'generic_party', 'sequence' => 1, 'status' => 'signed', 'completed_at' => '2026-09-02T10:00:00+00:00'],
    ['role_key' => 'generic_party', 'sequence' => 2, 'status' => 'pending', 'completed_at' => null],
];
$restoredLegacySummary = SignatureRequestSummary::fromArray($legacySummary);
if ($restoredLegacySummary->routingMode !== SignatureRoutingMode::Parallel
    || $restoredLegacySummary->participants[0]->roleSlot !== 1
    || $restoredLegacySummary->participants[1]->roleSlot !== 2
    || $restoredLegacySummary->participants[0]->routingState !== SignatureParticipantRoutingState::Completed
    || $restoredLegacySummary->participants[0]->presentedRevision !== 0) {
    throw new RuntimeException('Legacy summaries must infer repeated role slots and preserve parallel routing compatibility.');
}

$completedArtifact = new SignatureRequestArtifactReference(
    publicId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3e1',
    completedChecksum: str_repeat('b', 64),
    manifestChecksum: str_repeat('c', 64),
    finalizedAt: '2026-09-02T10:01:00+00:00',
);
$completedSummary = new SignatureRequestSummary(
    publicId: $signatureRequestResult->publicId,
    status: SignatureRequestStatus::Completed,
    subject: $signatureRequestSubject,
    document: $signatureRequestDocument,
    expiresAt: $expiryPolicy->expiresAt,
    participants: [],
    completedArtifact: $completedArtifact,
);
if (SignatureRequestSummary::fromArray($completedSummary->toArray())->toArray() !== $completedSummary->toArray()
    || SignatureRequestOutcome::fromRequestStatus(SignatureRequestStatus::Completed) !== SignatureRequestOutcome::Completed
    || SignatureRequestOutcome::fromRequestStatus(SignatureRequestStatus::Declined) !== SignatureRequestOutcome::Declined
    || SignatureRequestOutcome::fromRequestStatus(SignatureRequestStatus::Expired) !== SignatureRequestOutcome::Expired
    || SignatureRequestOutcome::fromRequestStatus(SignatureRequestStatus::Cancelled) !== SignatureRequestOutcome::Cancelled
    || SignatureRequestOutcome::fromRequestStatus(SignatureRequestStatus::Failed) !== SignatureRequestOutcome::Failed) {
    throw new RuntimeException('Terminal signature outcomes and verified artifact references must round-trip exactly.');
}
signatureContractMustThrow(
    static fn () => new SignatureRequestArtifactReference(
        publicId: 'not-a-uuid',
        completedChecksum: str_repeat('b', 64),
        manifestChecksum: str_repeat('c', 64),
        finalizedAt: '2026-09-02T10:01:00+00:00',
    ),
    'Completed artifact references must reject invalid public identities.',
);

$signatureRequestQuery = new SignatureRequestQueryInput(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    requestPublicId: $signatureRequestResult->publicId,
);
$signatureRequestCancel = new SignatureRequestCancelInput(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    requestPublicId: $signatureRequestResult->publicId,
    idempotencyKey: 'signature-cancel:employment-contract-0001',
    correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3d9',
);
$signatureRequestResend = new SignatureRequestResendInput(
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    subject: $signatureRequestSubject,
    requestPublicId: $signatureRequestResult->publicId,
    idempotencyKey: 'signature-resend:employment-contract-0001:worker',
    correlationId: '018f43d0-9ab6-7e1b-8e8a-18f5089db3e0',
    participantSequence: 1,
);
$signatureRequestReissue = new SignatureRequestReissueInput(
    predecessorRequestPublicId: $signatureRequestResult->publicId,
    replacement: $signatureRequestSubmission,
);

if (SignatureRequestQueryInput::fromArray(
    $signatureRequestQuery->toArray(),
    $signatureContractLegalEntity,
    $signatureContractActor,
)->toArray() !== $signatureRequestQuery->toArray()
    || SignatureRequestCancelInput::fromArray(
        $signatureRequestCancel->toArray(),
        $signatureContractLegalEntity,
        $signatureContractActor,
    )->toArray() !== $signatureRequestCancel->toArray()
    || SignatureRequestResendInput::fromArray(
        $signatureRequestResend->toArray(),
        $signatureContractLegalEntity,
        $signatureContractActor,
    )->toArray() !== $signatureRequestResend->toArray()
    || SignatureRequestReissueInput::fromArray(
        $signatureRequestReissue->toArray(),
        $signatureContractLegalEntity,
        $signatureContractActor,
    )->toArray() !== $signatureRequestReissue->toArray()) {
    throw new RuntimeException('Signature request query and lifecycle inputs must preserve their scoped idempotent transport contracts.');
}

$signatureRequestActionResult = new SignatureRequestActionResult(
    requestPublicId: $signatureRequestResult->publicId,
    action: SignatureRequestAction::Cancel,
    actionStatus: SignatureRequestActionStatus::Accepted,
    requestStatus: SignatureRequestStatus::Cancelled,
);

if (SignatureRequestActionResult::fromArray($signatureRequestActionResult->toArray())->toArray() !== $signatureRequestActionResult->toArray()) {
    throw new RuntimeException('Signature request action results must round-trip their action and request states.');
}

foreach (['submit', 'summary', 'requestDetailHrefIfAuthorized', 'completedDocumentDetailHrefIfAuthorized', 'cancel', 'resend', 'reissue'] as $method) {
    if (! method_exists(SignatureHost::class, $method)) {
        throw new RuntimeException("SignatureHost must expose its app-neutral {$method} capability.");
    }
}

$signatureRequestError = new SignatureException(
    errorCode: SignatureErrorCode::ParticipantAssignmentInvalid,
    message: 'Each required signature field must be assigned exactly once.',
    context: ['required_field_count' => 3, 'participant_count' => 1],
);

if (SignatureException::fromArray($signatureRequestError->toArray())->toArray() !== $signatureRequestError->toArray()
    || SignatureErrorCode::RequestNotFound->value !== 'request_not_found'
    || SignatureErrorCode::RequestScopeMismatch->value !== 'request_scope_mismatch'
    || SignatureErrorCode::DocumentReferenceMismatch->value !== 'document_reference_mismatch'
    || SignatureErrorCode::ParticipantInvalid->value !== 'participant_invalid'
    || SignatureErrorCode::ParticipantAssignmentInvalid->value !== 'participant_assignment_invalid'
    || SignatureErrorCode::AuthenticationProfileUnavailable->value !== 'authentication_profile_unavailable'
    || SignatureErrorCode::AuthenticationMethodsInvalid->value !== 'authentication_methods_invalid'
    || SignatureErrorCode::ConsentPolicyUnavailable->value !== 'consent_policy_unavailable'
    || SignatureErrorCode::ExpiryPolicyInvalid->value !== 'expiry_policy_invalid'
    || SignatureErrorCode::RequestTerminal->value !== 'request_terminal') {
    throw new RuntimeException('Signature request error results must retain stable P2 taxonomy codes on round-trip.');
}

foreach (SignatureRequestStatus::cases() as $status) {
    if (SignatureRequestStatus::from($status->value) !== $status) {
        throw new RuntimeException('Signature request statuses must round-trip through their serialized values.');
    }
}

$signatureWait = new SignatureProcessWaitDescriptor(
    requestPublicId: $signatureRequestResult->publicId,
    allowedOutcomes: [
        SignatureRequestOutcome::Completed,
        SignatureRequestOutcome::Declined,
        SignatureRequestOutcome::Expired,
        SignatureRequestOutcome::Cancelled,
        SignatureRequestOutcome::Failed,
    ],
);
if (SignatureProcessWaitDescriptor::fromArray($signatureWait->toArray())->toArray() !== $signatureWait->toArray()
    || array_keys($signatureWait->toArray()) !== ['signature_request_public_id', 'allowed_terminal_outcomes']) {
    throw new RuntimeException('Signature Process waits must round-trip only request identity and allowed terminal outcomes.');
}

$signatureTerminalResult = new SignatureProcessTerminalResult(
    requestPublicId: $signatureRequestResult->publicId,
    outcome: SignatureRequestOutcome::Completed,
    occurredAt: '2026-09-02T10:01:00+00:00',
);
if (SignatureProcessTerminalResult::fromArray($signatureTerminalResult->toArray())->toArray() !== $signatureTerminalResult->toArray()
    || array_keys($signatureTerminalResult->toArray()) !== [
        'signature_request_public_id',
        'signature_terminal_outcome',
        'signature_occurred_at',
    ]) {
    throw new RuntimeException('Signature Process terminal results must round-trip through the fixed PII-free variable contract.');
}

$signatureWaitResult = ProcessWorkActionResult::waitingForSignature(
    output: ['signature_request_public_id' => $signatureRequestResult->publicId],
    wait: $signatureWait,
    timeoutAt: '2026-09-03T10:01:00+00:00',
    cancellationPolicy: 'cancel_request',
);
if ($signatureWaitResult->suspension?->toArray() !== [
    'kind' => 'signature',
    'topic' => 'signature.request.outcome.v1',
    'reference_id' => $signatureRequestResult->publicId,
    'phase' => 'awaiting_signature',
    'metadata' => [
        'wait' => $signatureWait->toArray(),
        'timeout_at' => '2026-09-03T10:01:00+00:00',
        'cancellation_policy' => 'cancel_request',
    ],
]) {
    throw new RuntimeException('Signature Process work-action suspension lost its wait, timer, or cancellation policy.');
}

signatureContractMustThrow(
    static fn () => new SignatureProcessWaitDescriptor(
        $signatureRequestResult->publicId,
        [SignatureRequestOutcome::Completed, SignatureRequestOutcome::Completed],
    ),
    'Signature Process waits must reject duplicate terminal outcomes.',
);
signatureContractMustThrow(
    static fn () => SignatureProcessWaitDescriptor::fromArray([
        'signature_request_public_id' => $signatureRequestResult->publicId,
        'allowed_terminal_outcomes' => ['accepted'],
    ]),
    'Signature Process waits must reject non-terminal outcome values.',
);
signatureContractMustThrow(
    static fn () => SignatureProcessWaitDescriptor::fromArray([
        'signature_request_public_id' => $signatureRequestResult->publicId,
        'allowed_terminal_outcomes' => [['completed']],
    ]),
    'Signature Process waits must reject non-scalar serialized outcomes.',
);
signatureContractMustThrow(
    static fn () => ProcessWorkActionResult::waitingForSignature(
        output: [],
        wait: $signatureWait,
        timeoutAt: 'tomorrow',
    ),
    'Signature Process waits must reject non-durable timeout timestamps.',
);

$signatureRequestLogJson = json_encode($signatureRequestSubmission->toLogSafeArray(), JSON_THROW_ON_ERROR)
    .json_encode($signatureRequestSummary->toLogSafeArray(), JSON_THROW_ON_ERROR)
    .json_encode($signatureParticipant->toLogSafeArray(), JSON_THROW_ON_ERROR);
if (str_contains($signatureRequestLogJson, 'Worker Sample')
    || str_contains($signatureRequestLogJson, 'worker@example.test')
    || str_contains($signatureRequestLogJson, 'worker+otp@example.test')
    || str_contains($signatureRequestLogJson, 'invitation_address')
    || str_contains($signatureRequestLogJson, 'email_otp_challenge_address')) {
    throw new RuntimeException('Signature request log-safe projections must not contain participant names or contact addresses.');
}

$bulkDescriptor = new SignatureBulkBindingDescriptor(
    appKey: 'sample-signature',
    bindingKey: 'sample-signature.contract',
    bindingVersion: '2.0',
    capabilityContractVersion: 1,
    supportedSubjectResourceKeys: ['sample-signature.contract'],
    roleAssignmentPolicies: [
        new SignatureBulkRoleAssignmentPolicy('worker', [SignatureBulkParticipantAssignmentSource::BindingResolved]),
        new SignatureBulkRoleAssignmentPolicy('employer_representative', [
            SignatureBulkParticipantAssignmentSource::TemplateFixed,
            SignatureBulkParticipantAssignmentSource::RequestSupplied,
        ]),
        new SignatureBulkRoleAssignmentPolicy('witness', [SignatureBulkParticipantAssignmentSource::RequestSupplied]),
    ],
    supportedInvitationChannels: [SignatureInvitationChannel::Sms, SignatureInvitationChannel::Email],
    supportedAuthenticationMethods: [
        SignatureAuthenticationMethod::RequestPassword,
        SignatureAuthenticationMethod::EmailOtp,
    ],
    groupSelectorResourceKey: 'sample-signature.contract',
    groupSelectorLabelKey: 'sample-signature.bulk.selector.contract',
    groupSelectorRequired: true,
    status: DescriptorStatus::Deprecated,
);
$bulkDescriptorArray = $bulkDescriptor->toArray();
if (SignatureBulkBindingDescriptor::fromArray($bulkDescriptorArray)->toArray() !== $bulkDescriptorArray
    || $bulkDescriptor->descriptorKey() !== 'sample-signature.contract@2.0#1'
    || $bulkDescriptor->capabilityContractVersion !== 1
    || $bulkDescriptor->bindingVersion !== '2.0'
    || $bulkDescriptor->status !== DescriptorStatus::Deprecated
    || array_map(
        static fn (SignatureBulkParticipantAssignmentSource $source): string => $source->value,
        $bulkDescriptor->supportedAssignmentSources(),
    ) !== ['binding_resolved', 'request_supplied', 'template_fixed']
    || array_map(
        static fn (SignatureInvitationChannel $channel): string => $channel->value,
        $bulkDescriptor->supportedInvitationChannels,
    ) !== ['email', 'sms']
    || array_map(
        static fn (SignatureAuthenticationMethod $method): string => $method->value,
        $bulkDescriptor->supportedAuthenticationMethods,
    ) !== ['email_otp', 'request_password']
    || $bulkDescriptor->policyForRole('worker')?->allowedSources !== [SignatureBulkParticipantAssignmentSource::BindingResolved]) {
    throw new RuntimeException('Signature bulk descriptors must preserve exact capability identity and canonical role policy metadata.');
}

signatureContractMustThrow(
    static fn () => SignatureBulkBindingDescriptor::fromArray([
        ...$bulkDescriptorArray,
        'runtime_route' => '/apps/sample-signature/contracts',
    ]),
    'Signature bulk descriptors must reject undeclared runtime routes and arbitrary fields.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingDescriptor(
        appKey: 'sample-signature',
        bindingKey: 'sample-signature.contract',
        bindingVersion: '2.0',
        capabilityContractVersion: 0,
        supportedSubjectResourceKeys: ['sample-signature.contract'],
        roleAssignmentPolicies: [new SignatureBulkRoleAssignmentPolicy(
            'worker',
            [SignatureBulkParticipantAssignmentSource::BindingResolved],
        )],
        supportedInvitationChannels: [SignatureInvitationChannel::Email],
        supportedAuthenticationMethods: [SignatureAuthenticationMethod::EmailOtp],
    ),
    'Signature bulk capability contract versions must be positive and distinct from binding versions.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkRoleAssignmentPolicy('worker', [
        SignatureBulkParticipantAssignmentSource::RequestSupplied,
        SignatureBulkParticipantAssignmentSource::RequestSupplied,
    ]),
    'Signature bulk role policies must reject duplicate assignment sources.',
);
signatureContractMustThrow(
    static fn () => SignatureBulkTemplateParticipantAssignment::fromArray([
        'role_key' => 'worker',
        'participant_slot' => 1,
        'assignment_source' => 'caller_forged',
        'fixed_party_public_id' => null,
    ]),
    'Signature bulk assignments must reject unknown assignment source values.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingDescriptor(
        appKey: 'sample-signature',
        bindingKey: 'sample-signature.contract',
        bindingVersion: '2.0',
        capabilityContractVersion: 1,
        supportedSubjectResourceKeys: ['sample-signature.contract'],
        roleAssignmentPolicies: [new SignatureBulkRoleAssignmentPolicy(
            'worker',
            [SignatureBulkParticipantAssignmentSource::BindingResolved],
        )],
        supportedInvitationChannels: [SignatureInvitationChannel::Email],
        supportedAuthenticationMethods: [SignatureAuthenticationMethod::EmailOtp],
        groupSelectorRequired: true,
    ),
    'Required signature bulk selectors must declare an exact resource and label key.',
);

$bulkTemplateAssignments = [
    new SignatureBulkTemplateParticipantAssignment(
        'worker',
        1,
        SignatureBulkParticipantAssignmentSource::BindingResolved,
    ),
    new SignatureBulkTemplateParticipantAssignment(
        'employer_representative',
        1,
        SignatureBulkParticipantAssignmentSource::TemplateFixed,
        'party-fixed',
    ),
    new SignatureBulkTemplateParticipantAssignment(
        'witness',
        1,
        SignatureBulkParticipantAssignmentSource::RequestSupplied,
    ),
];
$bulkRequestedAssignment = new SignatureBulkRequestedParticipantAssignment('witness', 1, 'party-requested');
$bulkResolvedAssignments = [
    new SignatureBulkResolvedParticipantAssignment(
        'employer_representative',
        1,
        SignatureBulkParticipantAssignmentSource::TemplateFixed,
        'party-fixed',
    ),
    new SignatureBulkResolvedParticipantAssignment(
        'worker',
        1,
        SignatureBulkParticipantAssignmentSource::BindingResolved,
        'party-resolved',
    ),
    new SignatureBulkResolvedParticipantAssignment(
        'witness',
        1,
        SignatureBulkParticipantAssignmentSource::RequestSupplied,
        'party-requested',
    ),
];
$bulkSettings = [
    new SignatureBulkParticipantSetting(
        'worker',
        1,
        SignatureInvitationChannel::Email,
        [SignatureAuthenticationMethod::RequestPassword, SignatureAuthenticationMethod::EmailOtp],
    ),
    new SignatureBulkParticipantSetting(
        'employer_representative',
        1,
        SignatureInvitationChannel::Email,
        [SignatureAuthenticationMethod::EmailOtp],
    ),
    new SignatureBulkParticipantSetting(
        'witness',
        1,
        SignatureInvitationChannel::Email,
        [SignatureAuthenticationMethod::RequestPassword],
    ),
];

foreach ($bulkTemplateAssignments as $assignment) {
    if (SignatureBulkTemplateParticipantAssignment::fromArray($assignment->toArray())->toArray() !== $assignment->toArray()) {
        throw new RuntimeException('Signature bulk template assignments must round-trip their immutable authority.');
    }
}
if (SignatureBulkRequestedParticipantAssignment::fromArray($bulkRequestedAssignment->toArray())->toArray()
        !== $bulkRequestedAssignment->toArray()
    || $bulkRequestedAssignment->source() !== SignatureBulkParticipantAssignmentSource::RequestSupplied
    || array_keys($bulkRequestedAssignment->toArray()) !== [
        'role_key',
        'participant_slot',
        'recipient_type',
        'party_public_id',
        'display_name',
        'email',
    ]) {
    throw new RuntimeException('Signature bulk requested assignments must remain request-supplied by construction.');
}
$bulkExternalRequestedAssignment = SignatureBulkRequestedParticipantAssignment::external(
    'witness',
    2,
    'External Witness',
    'witness@example.test',
);
if (SignatureBulkRequestedParticipantAssignment::fromArray($bulkExternalRequestedAssignment->toArray())->toArray()
        !== $bulkExternalRequestedAssignment->toArray()
    || $bulkExternalRequestedAssignment->recipientIdentity() !== 'external:'.hash('sha256', 'witness@example.test')) {
    throw new RuntimeException('External bulk requested assignments must round-trip their normalized recipient facts.');
}
$bulkExternalResolvedAssignment = SignatureBulkResolvedParticipantAssignment::external(
    'witness',
    2,
    SignatureBulkParticipantAssignmentSource::RequestSupplied,
    'witness@example.test',
);
if (SignatureBulkResolvedParticipantAssignment::fromArray($bulkExternalResolvedAssignment->toArray())->toArray()
        !== $bulkExternalResolvedAssignment->toArray()
    || str_contains(json_encode($bulkExternalResolvedAssignment->toArray(), JSON_THROW_ON_ERROR), 'witness@example.test')) {
    throw new RuntimeException('Resolved external bulk assignments must retain only a redacted recipient fingerprint.');
}
foreach ($bulkResolvedAssignments as $assignment) {
    if (SignatureBulkResolvedParticipantAssignment::fromArray($assignment->toArray())->toArray() !== $assignment->toArray()) {
        throw new RuntimeException('Signature bulk resolved assignments must round-trip exact source and Party identity.');
    }
}
foreach ([
    static fn () => SignatureBulkTemplateParticipantAssignment::fromArray([
        ...$bulkTemplateAssignments[0]->toArray(),
        'caller_party_public_id' => 'forged-party',
    ]),
    static fn () => SignatureBulkRequestedParticipantAssignment::fromArray([
        ...$bulkRequestedAssignment->toArray(),
        'assignment_source' => 'template_fixed',
    ]),
    static fn () => SignatureBulkResolvedParticipantAssignment::fromArray([
        ...$bulkResolvedAssignments[0]->toArray(),
        'provider_model' => stdClass::class,
    ]),
] as $unknownAssignmentShape) {
    signatureContractMustThrow(
        $unknownAssignmentShape,
        'Signature bulk assignment DTOs must reject fields from a different trust boundary.',
    );
}
signatureContractMustThrow(
    static fn () => new SignatureBulkTemplateParticipantAssignment(
        'worker',
        1,
        SignatureBulkParticipantAssignmentSource::BindingResolved,
        'forged-party',
    ),
    'Binding-resolved template assignments must reject caller or template Party IDs.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkTemplateParticipantAssignment(
        'worker',
        1,
        SignatureBulkParticipantAssignmentSource::TemplateFixed,
    ),
    'Template-fixed assignments must require an exact fixed Party public ID.',
);

$bulkSelection = new SignatureBulkGroupSelection(
    'row-1',
    new ResourceRef(
        'sample-signature',
        'sample-signature.contract',
        'contract-public-id',
        'Protected contract display',
        '/apps/sample-signature/contracts/contract-public-id',
    ),
);
$bulkSelectionArray = $bulkSelection->toArray();
$bulkSelectionWithoutRef = new SignatureBulkGroupSelection('row-without-selection');
if (SignatureBulkGroupSelection::fromArray($bulkSelectionArray)->toArray() !== $bulkSelectionArray
    || SignatureBulkGroupSelection::fromArray($bulkSelectionWithoutRef->toArray())->selectionRef !== null
    || array_keys($bulkSelectionArray['selection_ref']) !== ['app_key', 'resource_key', 'resource_id']
    || str_contains(json_encode($bulkSelectionArray, JSON_THROW_ON_ERROR), 'Protected contract display')
    || str_contains(json_encode($bulkSelectionArray, JSON_THROW_ON_ERROR), '/apps/sample-signature')) {
    throw new RuntimeException('Signature bulk selections must serialize only canonical ResourceRef identity.');
}
signatureContractMustThrow(
    static fn () => SignatureBulkGroupSelection::fromArray([
        'client_key' => 'row-1',
        'selection_ref' => [
            'app_key' => 'sample-signature',
            'resource_key' => 'sample-signature.contract',
            'resource_id' => 'contract-public-id',
            'display' => 'must not be accepted',
        ],
    ]),
    'Signature bulk selections must reject ResourceRef display, href, and arbitrary App payload fields.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkGroupSelection(str_repeat('x', SignatureBulkGroupSelection::MAX_CLIENT_KEY_LENGTH + 1)),
    'Signature bulk group client keys must be bounded.',
);

$canonicalSetting = $bulkSettings[0];
if (SignatureBulkParticipantSetting::fromArray($canonicalSetting->toArray())->toArray() !== $canonicalSetting->toArray()
    || $canonicalSetting->toArray()['authentication_methods'] !== ['email_otp', 'request_password']
    || array_key_exists('password', $canonicalSetting->toArray())
    || array_key_exists('request_password_verifier', $canonicalSetting->toArray())) {
    throw new RuntimeException('Signature bulk participant settings must canonicalize enum methods without credential material.');
}
signatureContractMustThrow(
    static fn () => SignatureBulkParticipantSetting::fromArray([
        ...$canonicalSetting->toArray(),
        'password' => 'must-never-cross-the-contract',
    ]),
    'Signature bulk participant settings must reject plaintext password fields.',
);
signatureContractMustThrow(
    static fn () => SignatureBulkParticipantSetting::fromArray([
        ...$canonicalSetting->toArray(),
        'request_password_verifier' => 'must-never-cross-the-contract',
    ]),
    'Signature bulk participant settings must reject verifier fields.',
);

$bulkTemplateProvenance = new SignatureBulkTemplateProvenance(
    templatePublicId: 'template-public-id',
    templateRevision: 3,
    templateKey: 'sample-signature.contract.standard',
    templateContentHash: str_repeat('a', 64),
    bindingKey: 'sample-signature.contract',
    bindingVersion: '2.0',
);
if (SignatureBulkTemplateProvenance::fromArray($bulkTemplateProvenance->toArray())->toArray()
    !== $bulkTemplateProvenance->toArray()) {
    throw new RuntimeException('Signature bulk template provenance must round-trip exact revision identity.');
}
signatureContractMustThrow(
    static fn () => SignatureBulkTemplateProvenance::fromArray([
        ...$bulkTemplateProvenance->toArray(),
        'template_model' => stdClass::class,
    ]),
    'Signature bulk template provenance must reject runtime and unknown fields.',
);

$bulkContractTenant = new class implements TenantIdentity
{
    public function key(): int|string
    {
        return 'tenant-public-id';
    }
};
$bulkPreviewContext = new SignatureBulkBindingPreviewContext(
    tenant: $bulkContractTenant,
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    template: $bulkTemplateProvenance,
    selection: $bulkSelection,
    templateAssignments: $bulkTemplateAssignments,
    requestedAssignments: [$bulkRequestedAssignment],
    participantSettings: $bulkSettings,
);
if ($bulkPreviewContext->templateAssignments !== $bulkTemplateAssignments
    || $bulkPreviewContext->participantSettings[0]->roleKey !== 'worker'
    || $bulkPreviewContext->participantSettings[1]->roleKey !== 'employer_representative'
    || $bulkPreviewContext->participantSettings[2]->roleKey !== 'witness') {
    throw new RuntimeException('Signature bulk preview contexts must preserve template order and align settings by exact slot.');
}
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingPreviewContext(
        tenant: $bulkContractTenant,
        legalEntity: $signatureContractLegalEntity,
        actor: $signatureContractActor,
        template: $bulkTemplateProvenance,
        selection: $bulkSelection,
        templateAssignments: $bulkTemplateAssignments,
        requestedAssignments: [],
        participantSettings: $bulkSettings,
    ),
    'Signature bulk preview contexts must exactly fill every request-supplied slot.',
);

$bulkPlan = new SignatureBulkBindingExecutionPlan(
    subject: new ResourceRef(
        'sample-signature',
        'sample-signature.contract',
        'contract-public-id',
        'Protected subject display',
        '/protected-subject-href',
    ),
    bindingKey: 'sample-signature.contract',
    bindingVersion: '2.0',
    capabilityContractVersion: 1,
    templateKey: 'sample-signature.contract.standard',
    protectedDocumentVariables: [
        'worker.government_id' => 'protected-government-id',
        'contract.salary' => 50000000,
    ],
    protectedSignatoryRoles: [
        'worker' => [['party_public_id' => 'party-resolved']],
        'employer_representative' => [['party_public_id' => 'party-fixed']],
        'witness' => [['party_public_id' => 'party-requested']],
    ],
    participantAssignments: $bulkResolvedAssignments,
    participantSettings: $bulkSettings,
    semanticFingerprint: str_repeat('b', 64),
    protectedDocumentVariableClassifications: [
        'worker.government_id' => SignatureDataClassification::Restricted,
        'contract.salary' => SignatureDataClassification::Confidential,
    ],
);
$bulkPlanArray = $bulkPlan->toArray();
$bulkPlanJson = json_encode($bulkPlanArray, JSON_THROW_ON_ERROR);
if (array_key_exists('document_variables', $bulkPlanArray)
    || array_key_exists('document_variable_classifications', $bulkPlanArray)
    || array_key_exists('signatory_roles', $bulkPlanArray)
    || array_column($bulkPlanArray['participant_assignments'], 'role_key')
        !== ['employer_representative', 'worker', 'witness']
    || array_column($bulkPlanArray['participant_settings'], 'role_key')
        !== ['employer_representative', 'worker', 'witness']
    || str_contains($bulkPlanJson, 'protected-government-id')
    || str_contains($bulkPlanJson, 'Protected subject display')
    || str_contains($bulkPlanJson, '/protected-subject-href')
    || $bulkPlan->protectedPayload()['document_variables']['contract.salary'] !== 50000000
    || $bulkPlan->protectedPayload()['document_variable_classifications'] !== [
        'worker.government_id' => 'restricted',
        'contract.salary' => 'confidential',
    ]
    || SignatureBulkBindingExecutionPlan::fromArray(
        $bulkPlanArray,
        $bulkPlan->protectedPayload(),
    )->protectedPayload() !== $bulkPlan->protectedPayload()) {
    throw new RuntimeException('Signature bulk execution plans must redact ordinary serialization and preserve explicit protected payloads.');
}
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingExecutionPlan(
        subject: $bulkPlan->subject,
        bindingKey: $bulkPlan->bindingKey,
        bindingVersion: $bulkPlan->bindingVersion,
        capabilityContractVersion: $bulkPlan->capabilityContractVersion,
        templateKey: $bulkPlan->templateKey,
        protectedDocumentVariables: ['raw_model' => new stdClass],
        protectedSignatoryRoles: $bulkPlan->protectedPayload()['signatory_roles'],
        participantAssignments: $bulkResolvedAssignments,
        participantSettings: $bulkSettings,
        semanticFingerprint: str_repeat('b', 64),
        protectedDocumentVariableClassifications: ['raw_model' => SignatureDataClassification::Restricted],
    ),
    'Signature bulk protected plans must reject models, closures, uploads, and every non-JSON value.',
);
$nonScalarBulkRoles = $bulkPlan->protectedPayload()['signatory_roles'];
$nonScalarBulkRoles['worker'][0]['nested'] = ['not' => 'scalar'];
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingExecutionPlan(
        subject: $bulkPlan->subject,
        bindingKey: $bulkPlan->bindingKey,
        bindingVersion: $bulkPlan->bindingVersion,
        capabilityContractVersion: $bulkPlan->capabilityContractVersion,
        templateKey: $bulkPlan->templateKey,
        protectedDocumentVariables: [],
        protectedSignatoryRoles: $nonScalarBulkRoles,
        participantAssignments: $bulkResolvedAssignments,
        participantSettings: $bulkSettings,
        semanticFingerprint: str_repeat('b', 64),
    ),
    'Signature bulk protected signatory roles must reject non-scalar snapshot values.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingExecutionPlan(
        subject: $bulkPlan->subject,
        bindingKey: $bulkPlan->bindingKey,
        bindingVersion: $bulkPlan->bindingVersion,
        capabilityContractVersion: $bulkPlan->capabilityContractVersion,
        templateKey: $bulkPlan->templateKey,
        protectedDocumentVariables: [],
        protectedSignatoryRoles: $bulkPlan->protectedPayload()['signatory_roles'],
        participantAssignments: [
            $bulkResolvedAssignments[0],
            new SignatureBulkResolvedParticipantAssignment(
                'worker',
                1,
                SignatureBulkParticipantAssignmentSource::BindingResolved,
                'party-fixed',
            ),
        ],
        participantSettings: [$bulkSettings[1], $bulkSettings[0]],
        semanticFingerprint: str_repeat('b', 64),
    ),
    'Signature bulk execution plans must reject duplicate Party assignments across slots.',
);
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingExecutionPlan(
        subject: $bulkPlan->subject,
        bindingKey: $bulkPlan->bindingKey,
        bindingVersion: $bulkPlan->bindingVersion,
        capabilityContractVersion: $bulkPlan->capabilityContractVersion,
        templateKey: $bulkPlan->templateKey,
        protectedDocumentVariables: ['worker.full_name' => 'Worker'],
        protectedSignatoryRoles: $bulkPlan->protectedPayload()['signatory_roles'],
        participantAssignments: $bulkResolvedAssignments,
        participantSettings: $bulkSettings,
        semanticFingerprint: str_repeat('b', 64),
    ),
    'Every signature bulk protected variable must carry an explicit data classification.',
);
signatureContractMustThrow(
    static fn () => SignatureBulkBindingExecutionPlan::fromArray(
        $bulkPlan->toArray(),
        [...$bulkPlan->protectedPayload(), 'unexpected_protected_data' => true],
    ),
    'Signature bulk protected plan rehydration must reject unknown fields.',
);
signatureContractMustThrow(
    static fn () => SignatureBulkBindingExecutionPlan::fromArray(
        $bulkPlan->toArray(),
        [
            ...$bulkPlan->protectedPayload(),
            'document_variable_classifications' => [
                'worker.government_id' => 'top_secret',
                'contract.salary' => 'confidential',
            ],
        ],
    ),
    'Signature bulk protected variables must reject unknown data classifications.',
);

$oversizedBulkVariables = [];
$oversizedBulkVariableClassifications = [];
for ($index = 0; $index < 31; $index++) {
    $key = 'field_'.$index;
    $oversizedBulkVariables[$key] = str_repeat('x', 16300);
    $oversizedBulkVariableClassifications[$key] = SignatureDataClassification::Restricted;
}
$oversizedBulkRoles = $bulkPlan->protectedPayload()['signatory_roles'];
foreach ($oversizedBulkRoles as &$oversizedBulkRoleSubjects) {
    $oversizedBulkRoleSubjects[0]['protected_evidence'] = str_repeat('y', 7000);
}
unset($oversizedBulkRoleSubjects);
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingExecutionPlan(
        subject: $bulkPlan->subject,
        bindingKey: $bulkPlan->bindingKey,
        bindingVersion: $bulkPlan->bindingVersion,
        capabilityContractVersion: $bulkPlan->capabilityContractVersion,
        templateKey: $bulkPlan->templateKey,
        protectedDocumentVariables: $oversizedBulkVariables,
        protectedSignatoryRoles: $oversizedBulkRoles,
        participantAssignments: $bulkResolvedAssignments,
        participantSettings: $bulkSettings,
        semanticFingerprint: str_repeat('b', 64),
        protectedDocumentVariableClassifications: $oversizedBulkVariableClassifications,
    ),
    'Signature bulk protected execution plans must reject aggregate payloads above the shared snapshot bound.',
);

$bulkResolvedResult = SignatureBulkBindingPreviewResult::resolved($bulkPlan);
$bulkResolvedResultArray = $bulkResolvedResult->toArray();
if ($bulkResolvedResult->status !== SignatureBulkBindingPreviewStatus::Resolved
    || SignatureBulkBindingPreviewResult::fromArray(
        $bulkResolvedResultArray,
        $bulkPlan->protectedPayload(),
    )->toArray() !== $bulkResolvedResultArray
    || str_contains(json_encode($bulkResolvedResultArray, JSON_THROW_ON_ERROR), 'protected-government-id')) {
    throw new RuntimeException('Resolved signature bulk provider results must round-trip only a redacted plan plus explicit protected payload.');
}
$bulkRejectedResult = SignatureBulkBindingPreviewResult::rejected(
    SignatureBulkBindingFailureReason::SubjectUnauthorized,
    new SignatureBulkBindingParticipantFailure(
        'signer',
        2,
        SignatureBulkParticipantAssignmentSource::RequestSupplied,
    ),
);
if (SignatureBulkBindingPreviewResult::fromArray($bulkRejectedResult->toArray())->toArray()
        !== $bulkRejectedResult->toArray()
    || $bulkRejectedResult->reason !== SignatureBulkBindingFailureReason::SubjectUnauthorized
    || $bulkRejectedResult->participantFailure?->participantSlot !== 2) {
    throw new RuntimeException('Rejected signature bulk provider results must preserve stable non-sensitive reason codes.');
}
$bulkUnavailableResult = SignatureBulkBindingPreviewResult::unavailable(
    SignatureBulkBindingFailureReason::ProviderVersionMismatch,
);
if ($bulkUnavailableResult->status !== SignatureBulkBindingPreviewStatus::Unavailable
    || $bulkUnavailableResult->plan !== null) {
    throw new RuntimeException('Unavailable signature bulk provider results must not expose a plan.');
}

$bulkExecutionContext = new SignatureBulkBindingExecutionContext(
    tenant: $bulkContractTenant,
    legalEntity: $signatureContractLegalEntity,
    actor: $signatureContractActor,
    template: $bulkTemplateProvenance,
    frozenSubject: new ResourceRef(
        'sample-signature',
        'sample-signature.contract',
        'contract-public-id',
        'A display change must not alter exact identity',
    ),
    frozenSemanticFingerprint: str_repeat('b', 64),
    frozenPlan: $bulkPlan,
);
if ($bulkExecutionContext->frozenPlan !== $bulkPlan) {
    throw new RuntimeException('Signature bulk execution contexts must retain the exact frozen plan for reauthorization.');
}
signatureContractMustThrow(
    static fn () => new SignatureBulkBindingExecutionContext(
        tenant: $bulkContractTenant,
        legalEntity: $signatureContractLegalEntity,
        actor: $signatureContractActor,
        template: $bulkTemplateProvenance,
        frozenSubject: $bulkPlan->subject,
        frozenSemanticFingerprint: str_repeat('c', 64),
        frozenPlan: $bulkPlan,
    ),
    'Signature bulk execution contexts must reject semantic fingerprint drift.',
);

$bulkAuthorized = SignatureBulkBindingReauthorizationResult::authorized();
$bulkDenied = SignatureBulkBindingReauthorizationResult::denied(
    SignatureBulkBindingFailureReason::AuthorizationRevoked,
);
$bulkReauthorizationUnavailable = SignatureBulkBindingReauthorizationResult::unavailable(
    SignatureBulkBindingFailureReason::ProviderUnavailable,
);
if (SignatureBulkBindingReauthorizationResult::fromArray($bulkAuthorized->toArray())->status
        !== SignatureBulkBindingReauthorizationStatus::Authorized
    || SignatureBulkBindingReauthorizationResult::fromArray($bulkDenied->toArray())->reason
        !== SignatureBulkBindingFailureReason::AuthorizationRevoked
    || SignatureBulkBindingReauthorizationResult::fromArray($bulkReauthorizationUnavailable->toArray())->status
        !== SignatureBulkBindingReauthorizationStatus::Unavailable) {
    throw new RuntimeException('Signature bulk reauthorization must return typed outcomes without replacement plans.');
}
