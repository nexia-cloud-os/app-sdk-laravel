<?php

declare(strict_types=1);

use Nexia\Access\Contracts\RoleCatalog;
use Nexia\AppDescriptors\Contracts\ResourceSummaries;
use Nexia\Approval\ApprovalAttachmentEvidence;
use Nexia\Approval\ApprovalCaseReference;
use Nexia\Approval\ApprovalDocumentPreview;
use Nexia\Approval\ApprovalIdempotencyFingerprint;
use Nexia\Approval\Contracts\ApprovalHost;
use Nexia\Approval\Contracts\ApprovalIdempotency;
use Nexia\Approval\Contracts\ApprovalResolverRegistrar;
use Nexia\AppRuntime\AbstractPackageAppManifest;
use Nexia\AppRuntime\AppAvailability;
use Nexia\AppRuntime\AppDefinition;
use Nexia\AppRuntime\Contracts\AppRegistrar;
use Nexia\Attachments\Contracts\AttachmentStore;
use Nexia\Attachments\Contracts\AuthorizedFileReader;
use Nexia\Documents\Contracts\PdfFontProvider;
use Nexia\Documents\PdfFontAsset;
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\ActorDirectory;
use Nexia\Identity\Contracts\Party;
use Nexia\Identity\Contracts\PartyAccess;
use Nexia\Identity\Contracts\PartyRelationshipDirectory;
use Nexia\Identity\Contracts\PersonLoginInvitations;
use Nexia\Identity\Contracts\PersonPartyProvisioner;
use Nexia\Identity\Contracts\PersonProfileHistory;
use Nexia\Identity\PersonLoginInvitationAccessProfile;
use Nexia\Identity\PersonProfileChangeView;
use Nexia\Identity\PersonProfileHistoryPage;
use Nexia\Laravel\Access\Contracts\SubjectPermissionAuthorizer;
use Nexia\Laravel\Access\Contracts\TenantPermissionSnapshotAuthorizer;
use Nexia\Laravel\Identity\Contracts\PartyDirectory;
use Nexia\Laravel\Models\Contracts\HostReferenceResolver;
use Nexia\ResourceReference\Contracts\ReferenceAvailability;
use Nexia\Laravel\Testing\Contracts\HostTestModelStore;
use Nexia\Mutation\Contracts\MutationPublisher;
use Nexia\Navigation\Contracts\NavigationDestinations;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;
use Nexia\Organization\Contracts\OperatingUnitEvidenceDirectory;
use Nexia\Organization\Contracts\OrganizationDirectory;
use Nexia\Organization\OperatingUnitEvidence;
use Nexia\Permission\Contracts\LegalEntityMemberSelfAccessContribution;
use Nexia\Process\Contracts\AssignmentGroups;
use Nexia\Process\Contracts\ProcessRuntime;
use Nexia\Process\Domain\Enums\ProcessInstanceStatus;
use Nexia\Process\ProcessInstanceSnapshot;
use Nexia\Process\ReceiveTaskCorrelation;
use Nexia\ResourceImport\Contracts\ImportPlanProvider;
use Nexia\ResourceReference\ReferenceStatus;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Security\Contracts\RecentAuthentication;
use Nexia\Signature\Contracts\SignatureBulkBindingContribution;
use Nexia\Signature\Contracts\SignatureBulkBindingProvider;
use Nexia\Signature\SignatureBulkBindingExecutionContext;
use Nexia\Signature\SignatureBulkBindingPreviewContext;
use Nexia\Signature\SignatureBulkBindingPreviewResult;
use Nexia\Signature\SignatureBulkBindingReauthorizationResult;
use Nexia\Tenancy\Contracts\TenantSettings;
use Nexia\Testing\Contracts\ApprovalTestHost;
use Nexia\Testing\Contracts\ContributionTestHost;
use Nexia\Testing\Contracts\HostTestStore;

require dirname(__DIR__).'/vendor/autoload.php';

$definition = new AppDefinition(
    manifestClass: 'Nexia\\Tests\\SampleManifest',
    appFamily: 'finance',
    appName: 'Sample',
    appKey: 'sample',
    appTablePrefix: 'expense',
);
$manifest = new class($definition) extends AbstractPackageAppManifest {};

if ($manifest->id() !== 'sample' || $manifest->name() !== 'Sample') {
    throw new RuntimeException('Package manifest identity contract changed unexpectedly.');
}

$preview = new ApprovalDocumentPreview('sample.title', [], 'sample.expense_report', '1.0', 'ko');
$case = new ApprovalCaseReference('case-public-id');
$attachments = [new ApprovalAttachmentEvidence('media-public-id', 'checksum', 'clean')];
$resourceRef = new ResourceRef('sample', 'sample.expense_report', 'report-public-id', 'Expense report');
$process = new ProcessInstanceSnapshot(
    publicId: 'process-public-id',
    legalEntityKey: 7,
    status: ProcessInstanceStatus::Running,
    businessKey: 'EXP-7',
    resourceRef: $resourceRef,
);
$correlation = new ReceiveTaskCorrelation(
    legalEntityKey: 7,
    processInstancePublicId: $process->publicId,
    businessKey: $process->businessKey,
    resourceRef: $resourceRef,
    elementId: 'wait_approval',
    topic: 'sample.approval.recorded',
    payload: ['outcome' => 'approved'],
    requiredInstanceStatus: ProcessInstanceStatus::Running,
);
$profileChange = new PersonProfileChangeView(
    occurredAt: '2026-08-20T10:20:30+09:00',
    actorLabel: 'HR Manager',
    reason: 'Corrected after document review',
    changes: [['field' => 'display_name', 'before' => 'Before', 'after' => 'After']],
);
$profileHistoryPage = new PersonProfileHistoryPage([$profileChange], 'next-page');
if ($preview->toArray()['schema_key'] !== 'sample.expense_report'
    || $case->publicId !== 'case-public-id'
    || $attachments[0]->scanVerdict !== 'clean'
    || $correlation->resourceRef !== $process->resourceRef
    || ReferenceStatus::Unauthorized->value !== 'unauthorized'
    || $profileChange->changes[0]['field'] !== 'display_name'
    || $profileHistoryPage->nextCursor !== 'next-page') {
    throw new RuntimeException('Host boundary DTO contract changed unexpectedly.');
}

if (! AppAvailability::Unavailable->permitsManualFallback()
    || AppAvailability::Disabled->permitsManualFallback()
    || AppAvailability::Failed->permitsManualFallback()
    || AppAvailability::Stale->permitsManualFallback()
    || AppAvailability::Unauthorized->permitsManualFallback()) {
    throw new RuntimeException('Only an absent target App may permit manual fallback.');
}

if (ApprovalIdempotencyFingerprint::forPayload(['b' => 2, 'a' => ['z' => 3, 'y' => 1]])
    !== ApprovalIdempotencyFingerprint::forPayload(['a' => ['y' => 1, 'z' => 3], 'b' => 2])) {
    throw new RuntimeException('Approval idempotency fingerprints must be key-order stable.');
}

if (ApprovalIdempotencyFingerprint::forPayload(['b' => 2, 'a' => ['z' => 3, 'y' => 1]])
    !== '7dbefa38495e948c0c1d0bb972ac792f240cf60d877f03e14c6f9bbcbde52bf7') {
    throw new RuntimeException('Approval idempotency fingerprint compatibility changed unexpectedly.');
}

$approvalRouteContext = new ReflectionMethod(ApprovalHost::class, 'resolveRoute')
    ->getParameters()[1]
    ->getType();
if (! $approvalRouteContext instanceof ReflectionNamedType || ! $approvalRouteContext->allowsNull()) {
    throw new RuntimeException('Approval routes must accept a tenant-scoped context.');
}

$preparation = new ReflectionMethod(ApprovalIdempotency::class, 'execute')->getParameters()[2];
if ($preparation->getName() !== 'prepare' || ! $preparation->isDefaultValueAvailable()
    || $preparation->getDefaultValue() !== null || ! $preparation->getType()?->allowsNull()) {
    throw new RuntimeException('Approval idempotency preparation must remain optional for existing callers.');
}

foreach ([
    Actor::class,
    ActorDirectory::class,
    AuthorizedFileReader::class,
    AttachmentStore::class,
    LegalEntity::class,
    ReferenceAvailability::class,
    NavigationDestinations::class,
    ApprovalHost::class,
    ApprovalIdempotency::class,
    ApprovalResolverRegistrar::class,
    AppRegistrar::class,
    AssignmentGroups::class,
    ApprovalTestHost::class,
    ContributionTestHost::class,
    HostTestStore::class,
    HostTestModelStore::class,
    ImportPlanProvider::class,
    MutationPublisher::class,
    LegalEntityMemberSelfAccessContribution::class,
    ProcessRuntime::class,
    RecentAuthentication::class,
    ResourceSummaries::class,
    OperatingUnit::class,
    OperatingUnitEvidenceDirectory::class,
    OrganizationDirectory::class,
    Party::class,
    PartyAccess::class,
    PersonLoginInvitations::class,
    PersonPartyProvisioner::class,
    PersonProfileHistory::class,
    PartyDirectory::class,
    PartyRelationshipDirectory::class,
    HostReferenceResolver::class,
    RoleCatalog::class,
    SubjectPermissionAuthorizer::class,
    TenantPermissionSnapshotAuthorizer::class,
    TenantSettings::class,
    SignatureBulkBindingContribution::class,
    SignatureBulkBindingProvider::class,
] as $contract) {
    if (! interface_exists($contract)) {
        throw new RuntimeException("Host boundary contract [{$contract}] is missing.");
    }
}

foreach ([
    Actor::class => ['key', 'publicId', 'displayLabel', 'partyKey', 'isVerified'],
    ActorDirectory::class => ['findByKey', 'requireByKey', 'findByPublicId', 'findByPartyKey'],
    AuthorizedFileReader::class => ['read'],
    AttachmentStore::class => ['createGeneratedFile', 'attach', 'exists', 'fileForAttachment', 'downloadHrefIfAuthorized'],
    PdfFontProvider::class => ['forLocale'],
    LegalEntity::class => ['key', 'publicId', 'displayLabel', 'code', 'partyPublicId', 'isActiveOrganization'],
    OperatingUnit::class => ['key', 'publicId', 'displayLabel'],
    OperatingUnitEvidenceDirectory::class => ['findByKey', 'effectiveForLegalEntity', 'lockForLegalEntity'],
    OrganizationDirectory::class => ['findLegalEntity', 'legalEntity', 'findActiveLegalEntity', 'findActiveLegalEntityByKey', 'findOperatingUnit', 'operatingUnit'],
    Party::class => ['key', 'publicId', 'displayLabel', 'isPerson', 'isOrganization', 'isArchived'],
    PartyAccess::class => ['allows'],
    PersonLoginInvitations::class => ['status', 'invite'],
    PersonPartyProvisioner::class => ['ensureForDirectorySubject'],
    PersonProfileHistory::class => ['pageForAuthorizedHr'],
    PartyDirectory::class => ['findByPublicId', 'findCanonicalByPublicId', 'requireByPublicId', 'requireByPublicIdForUpdate', 'exists', 'findByPublicIds', 'orderByDisplayLabel'],
    PartyRelationshipDirectory::class => ['findVisibleByPublicId'],
    ProcessRuntime::class => ['hasPublishedEventStart', 'findInstance', 'correlateReceiveTask', 'deliverMessage'],
    RecentAuthentication::class => ['isFresh'],
    ApprovalIdempotency::class => ['execute'],
    ResourceSummaries::class => ['resolve', 'summarize'],
    ApprovalTestHost::class => ['createCase', 'caseCount', 'publishBusinessTemplate', 'approve'],
    ContributionTestHost::class => ['permissionDefinitions', 'rolePresets', 'pageElements', 'agentManifest'],
    HostTestStore::class => ['createProcessDefinition', 'createProcessInstance', 'createProcessToken', 'createFile'],
    ImportPlanProvider::class => ['planFor'],
    MutationPublisher::class => ['resourceChanged', 'actionSucceeded'],
    LegalEntityMemberSelfAccessContribution::class => ['legalEntityMemberSelfAccessKeys'],
    TenantPermissionSnapshotAuthorizer::class => ['forPermissions'],
    SignatureBulkBindingContribution::class => ['appDescriptors', 'signatureBulkBindingProviders'],
    SignatureBulkBindingProvider::class => [
        'appKey',
        'bindingKey',
        'bindingVersion',
        'capabilityContractVersion',
        'resolvePreview',
        'reauthorize',
    ],
] as $contract => $methods) {
    foreach ($methods as $method) {
        if (! method_exists($contract, $method)) {
            throw new RuntimeException("Host boundary contract [{$contract}] is missing [{$method}].");
        }
    }
}

$pdfFont = new PdfFontAsset('Noto Sans KR', '/host/fonts/NotoSansKR.ttf');
if ($pdfFont->family !== 'Noto Sans KR' || $pdfFont->path !== '/host/fonts/NotoSansKR.ttf') {
    throw new RuntimeException('PDF font assets must expose only the host-provided family and local path.');
}

$bulkPreviewMethod = new ReflectionMethod(SignatureBulkBindingProvider::class, 'resolvePreview');
$bulkReauthorizeMethod = new ReflectionMethod(SignatureBulkBindingProvider::class, 'reauthorize');
if ((string) $bulkPreviewMethod->getParameters()[0]->getType() !== SignatureBulkBindingPreviewContext::class
    || (string) $bulkPreviewMethod->getReturnType() !== SignatureBulkBindingPreviewResult::class
    || (string) $bulkReauthorizeMethod->getParameters()[0]->getType() !== SignatureBulkBindingExecutionContext::class
    || (string) $bulkReauthorizeMethod->getReturnType() !== SignatureBulkBindingReauthorizationResult::class) {
    throw new RuntimeException('Signature bulk provider boundaries must expose only App-neutral SDK context and result contracts.');
}

if (PersonLoginInvitationAccessProfile::LegalEntityMemberSelfService->value !== 'legal_entity_member_self_service') {
    throw new RuntimeException('Person login invitation access profile contract changed unexpectedly.');
}

$operatingUnitEvidence = new OperatingUnitEvidence(7, 'unit-public-id', 'Operations', 3, 'OPS', 'Operations', 'department');
if ($operatingUnitEvidence->revision !== 3 || $operatingUnitEvidence->publicId !== 'unit-public-id') {
    throw new RuntimeException('Operating Unit evidence contract changed unexpectedly.');
}

fwrite(STDOUT, "Host boundary contracts passed.\n");
