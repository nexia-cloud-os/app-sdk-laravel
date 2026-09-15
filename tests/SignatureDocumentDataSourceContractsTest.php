<?php

declare(strict_types=1);

use Nexia\AppDescriptors\AppDescriptorSet;
use Nexia\AppDescriptors\SignatureDocumentDataContractDescriptor;
use Nexia\AppDescriptors\SignatureDocumentDataFieldDescriptor;
use Nexia\AppDescriptors\SignatureDocumentDataSourceDescriptor;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\AbstractResourceBackedSignatureDocumentDataSourceProvider;
use Nexia\Signature\Contracts\SignatureDocumentDataSourceProvider;
use Nexia\Signature\ResourceBackedSignatureDocumentData;
use Nexia\Signature\SignatureDataClassification;
use Nexia\Signature\SignatureDocumentDataCandidate;
use Nexia\Signature\SignatureDocumentDataCardinality;
use Nexia\Signature\SignatureDocumentDataDiagnostic;
use Nexia\Signature\SignatureDocumentDataDiagnosticCode;
use Nexia\Signature\SignatureDocumentDataFieldType;
use Nexia\Signature\SignatureDocumentDataFormatter;
use Nexia\Signature\SignatureDocumentDataLimits;
use Nexia\Signature\SignatureDocumentDataLookupMode;
use Nexia\Signature\SignatureDocumentDataPurpose;
use Nexia\Signature\SignatureDocumentDataQuery;
use Nexia\Signature\SignatureDocumentDataResolvedItem;
use Nexia\Signature\SignatureDocumentDataResult;
use Nexia\Signature\SignatureDocumentDataStatus;
use Nexia\Tenancy\Contracts\TenantIdentity;

require dirname(__DIR__).'/vendor/autoload.php';

/** @param callable(): mixed $callback */
function documentDataMustThrow(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException|ValueError) {
        return;
    }

    throw new RuntimeException($message);
}

function documentDataField(
    string $key,
    SignatureDocumentDataFieldType $type = SignatureDocumentDataFieldType::String,
    bool $required = true,
    array $formatters = [SignatureDocumentDataFormatter::Plain],
    array $schema = [],
): SignatureDocumentDataFieldDescriptor {
    return new SignatureDocumentDataFieldDescriptor(
        key: $key,
        type: $type,
        required: $required,
        classification: SignatureDataClassification::Confidential,
        formatters: $formatters,
        labelKey: 'sample-owner.signature_document_data.fields.'.str_replace('.', '_', $key),
        schema: $schema,
    );
}

$fullName = documentDataField('worker_full_name');
$startDate = documentDataField(
    'start_date',
    SignatureDocumentDataFieldType::Date,
    formatters: [SignatureDocumentDataFormatter::DateIso, SignatureDocumentDataFormatter::DateLocal],
);
$salary = documentDataField(
    'annual_base_salary',
    SignatureDocumentDataFieldType::Money,
    formatters: [SignatureDocumentDataFormatter::MoneyWithCurrency],
);
$contract = new SignatureDocumentDataContractDescriptor(
    dataContractKey: 'sample-owner.employment_contract.compensation_terms',
    dataContractVersion: 1,
    fieldKeys: ['annual_base_salary'],
);
$descriptor = new SignatureDocumentDataSourceDescriptor(
    appKey: 'sample-owner',
    sourceKey: 'sample-owner.worker_employment',
    sourceVersion: 1,
    resourceKey: 'sample-owner.employment_contract',
    supportedSubjectResourceKeys: ['sample-owner.employment_contract'],
    cardinality: SignatureDocumentDataCardinality::One,
    lookupMode: SignatureDocumentDataLookupMode::DerivedRef,
    providedDataContracts: [$contract],
    fields: [$fullName, $startDate, $salary],
    syntheticSample: [
        'worker_full_name' => '홍길동',
        'start_date' => '2026-09-01',
        'annual_base_salary' => ['amount' => '60000000', 'currency' => 'KRW'],
    ],
    labelKey: 'sample-owner.signature_document_data.worker_employment.label',
    descriptionKey: 'sample-owner.signature_document_data.worker_employment.description',
    defaultSourceRefAnchor: 'worker',
);
$roundTrip = SignatureDocumentDataSourceDescriptor::fromArray($descriptor->toArray());
if ($roundTrip->toArray() !== $descriptor->toArray()
    || $descriptor->descriptorKey() !== 'sample-owner.worker_employment@1'
    || $roundTrip->resourceKey !== 'sample-owner.employment_contract'
    || AppDescriptorSet::of($descriptor)->ofType(SignatureDocumentDataSourceDescriptor::class) !== [$descriptor]
    || $contract->identity() !== 'sample-owner.employment_contract.compensation_terms@1') {
    throw new RuntimeException('Signature document data descriptors must round-trip with stable typed identity.');
}

$legacyDescriptor = new SignatureDocumentDataSourceDescriptor(
    appKey: 'sample-catalog',
    sourceKey: 'sample-catalog.assigned_assets',
    sourceVersion: 1,
    supportedSubjectResourceKeys: ['sample-owner.employment_contract'],
    cardinality: SignatureDocumentDataCardinality::One,
    lookupMode: SignatureDocumentDataLookupMode::DerivedRef,
    providedDataContracts: [],
    fields: [$fullName],
    syntheticSample: ['worker_full_name' => '홍길동'],
    labelKey: 'sample-catalog.signature_document_data.assigned_assets.label',
    descriptionKey: 'sample-catalog.signature_document_data.assigned_assets.description',
    defaultSourceRefAnchor: 'worker',
);
$legacyRoundTrip = SignatureDocumentDataSourceDescriptor::fromArray($legacyDescriptor->toArray());
if ($legacyDescriptor->resourceKey !== null
    || array_key_exists('resource_key', $legacyDescriptor->toArray())
    || $legacyRoundTrip->resourceKey !== null
    || $legacyRoundTrip->toArray() !== $legacyDescriptor->toArray()) {
    throw new RuntimeException('Legacy source descriptors must round-trip without Resource metadata.');
}

$tenant = new class implements TenantIdentity
{
    public function key(): int|string
    {
        return 'tenant-1';
    }
};
$legalEntity = new class implements LegalEntity
{
    public function key(): int|string
    {
        return 10;
    }

    public function publicId(): string
    {
        return '018f43d0-9ab6-7e1b-8e8a-18f5089db310';
    }

    public function displayLabel(): string
    {
        return 'Nexia Korea';
    }

    public function code(): string
    {
        return 'KR';
    }

    public function partyPublicId(): ?string
    {
        return '018f43d0-9ab6-7e1b-8e8a-18f5089db311';
    }

    public function isActiveOrganization(): bool
    {
        return true;
    }
};
$actor = new class implements Actor
{
    public function key(): int|string
    {
        return 20;
    }

    public function publicId(): string
    {
        return '018f43d0-9ab6-7e1b-8e8a-18f5089db320';
    }

    public function displayLabel(): string
    {
        return 'Coordinator';
    }

    public function partyKey(): int|string|null
    {
        return 30;
    }

    public function isVerified(): bool
    {
        return true;
    }
};
$subject = new ResourceRef(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.employment_contract',
    resourceId: 'contract-1',
    display: 'Employment Contract 1',
);
$worker = new ResourceRef(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.worker',
    resourceId: 'worker-1',
    display: 'Worker 1',
);
$nestedValueField = documentDataField('name');
$fieldTypeCases = [
    [documentDataField('a_string'), 'value', 1],
    [documentDataField('a_text', SignatureDocumentDataFieldType::Text), 'long value', false],
    [documentDataField('a_date', SignatureDocumentDataFieldType::Date, formatters: [SignatureDocumentDataFormatter::DateIso]), '2026-09-01', '09/01/2026'],
    [documentDataField('a_datetime', SignatureDocumentDataFieldType::DateTime, formatters: [SignatureDocumentDataFormatter::DateTimeLocal]), '2026-09-01T00:00:00+09:00', '2026-09-01'],
    [documentDataField('a_boolean', SignatureDocumentDataFieldType::Boolean, formatters: [SignatureDocumentDataFormatter::BooleanYesNo]), true, 1],
    [documentDataField('an_integer', SignatureDocumentDataFieldType::Integer, formatters: [SignatureDocumentDataFormatter::Integer]), 10, '10'],
    [documentDataField('a_decimal', SignatureDocumentDataFieldType::Decimal, formatters: [SignatureDocumentDataFormatter::Decimal]), '10.25', '01.2'],
    [documentDataField('money', SignatureDocumentDataFieldType::Money, formatters: [SignatureDocumentDataFormatter::MoneyWithCurrency]), ['amount' => '10.25', 'currency' => 'KRW'], ['amount' => 10]],
    [documentDataField('a_ref', SignatureDocumentDataFieldType::ResourceRef, formatters: [SignatureDocumentDataFormatter::ResourceLabel]), $worker, ['app_key' => 'sample-owner']],
    [documentDataField('an_object', SignatureDocumentDataFieldType::Object, formatters: [], schema: [$nestedValueField]), ['name' => 'value'], []],
    [documentDataField('a_list', SignatureDocumentDataFieldType::List, formatters: [], schema: [$nestedValueField]), [['name' => 'value']], ['value']],
];
foreach ($fieldTypeCases as [$fieldTypeCase, $acceptedValue, $rejectedValue]) {
    if (! $fieldTypeCase->accepts($acceptedValue) || $fieldTypeCase->accepts($rejectedValue)) {
        throw new RuntimeException("Signature document data field type [{$fieldTypeCase->type->value}] validation drifted.");
    }
}
$query = new SignatureDocumentDataQuery(
    tenant: $tenant,
    legalEntity: $legalEntity,
    actor: $actor,
    purpose: SignatureDocumentDataPurpose::SignatureRequestPreparation,
    appKey: 'sample-owner',
    sourceKey: 'sample-owner.worker_employment',
    sourceVersion: 1,
    subjectResourceRef: $subject,
    derivedSubjectAnchors: ['worker' => $worker],
    explicitSourceRef: null,
    asOf: new DateTimeImmutable('2026-09-01T00:00:00+09:00'),
    requestedFieldKeys: ['worker_full_name', 'start_date', 'annual_base_salary'],
);
$resolvedItem = new SignatureDocumentDataResolvedItem(
    resourceRef: $worker,
    sourceRevision: 'worker-profile-7',
    contentHash: str_repeat('a', 64),
    effectiveAt: new DateTimeImmutable('2026-09-01T00:00:00+09:00'),
    values: [
        'worker_full_name' => '홍길동',
        'start_date' => '2026-09-01',
        'annual_base_salary' => ['amount' => '60000000', 'currency' => 'KRW'],
    ],
    displayValues: ['annual_base_salary' => 'KRW 60,000,000'],
);
$resolved = new SignatureDocumentDataResult(
    status: SignatureDocumentDataStatus::Resolved,
    resolvedItems: [$resolvedItem],
);
$provider = new class($resolved) implements SignatureDocumentDataSourceProvider
{
    public function __construct(private SignatureDocumentDataResult $result) {}

    public function appKey(): string
    {
        return 'sample-owner';
    }

    public function sourceKey(): string
    {
        return 'sample-owner.worker_employment';
    }

    public function sourceVersion(): int
    {
        return 1;
    }

    public function resolve(SignatureDocumentDataQuery $query): SignatureDocumentDataResult
    {
        return $this->result;
    }
};
if ($provider->resolve($query) !== $resolved
    || $resolved->resolvedItems()[0]->protectedValues()['worker_full_name'] !== '홍길동'
    || str_contains((string) json_encode($resolved), '60000000')
    || str_contains((string) json_encode($resolvedItem), '60000000')) {
    throw new RuntimeException('Provider results must expose protected values only through the explicit accessor.');
}

$candidate = new SignatureDocumentDataCandidate(
    resourceRef: $worker,
    sourceRevision: 'worker-profile-7',
    contentHash: null,
);
$selection = new SignatureDocumentDataResult(
    status: SignatureDocumentDataStatus::SelectionRequired,
    candidates: [$candidate],
);
$unavailable = new SignatureDocumentDataResult(
    status: SignatureDocumentDataStatus::Unavailable,
    diagnostics: [new SignatureDocumentDataDiagnostic(SignatureDocumentDataDiagnosticCode::Timeout, 30)],
);
if ($selection->candidates()[0]->toArray()['resource_ref']['resource_id'] !== 'worker-1'
    || $unavailable->diagnostics()[0]->toArray() !== ['code' => 'timeout', 'retry_after_seconds' => 30]) {
    throw new RuntimeException('Candidate and value-free diagnostic projections changed unexpectedly.');
}

documentDataMustThrow(
    static fn () => new SignatureDocumentDataQuery(
        tenant: $tenant,
        legalEntity: $legalEntity,
        actor: $actor,
        purpose: SignatureDocumentDataPurpose::SignatureRequestPreparation,
        appKey: 'sample-evaluation',
        sourceKey: 'sample-owner.worker_employment',
        sourceVersion: 1,
        subjectResourceRef: $subject,
        derivedSubjectAnchors: [],
        explicitSourceRef: null,
        asOf: new DateTimeImmutable,
        requestedFieldKeys: ['worker_full_name'],
    ),
    'Query source app and key ownership must match.',
);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataCandidate($worker, null, null),
    'Candidates must carry exact source revision or content hash provenance.',
);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataResult(SignatureDocumentDataStatus::Resolved),
    'Resolved status must carry at least one resolved item.',
);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataResult(SignatureDocumentDataStatus::Missing, candidates: [$candidate]),
    'Non-resolved statuses must not expose candidates or protected values.',
);
documentDataMustThrow(
    static fn () => documentDataField(
        'wrong_formatter',
        SignatureDocumentDataFieldType::Date,
        formatters: [SignatureDocumentDataFormatter::MoneyWithCurrency],
    ),
    'Field descriptors must reject formatter and type mismatches.',
);
documentDataMustThrow(
    static fn () => SignatureDocumentDataFieldDescriptor::fromArray([
        'key' => 'missing_label',
        'type' => 'string',
        'required' => true,
        'classification' => 'confidential',
        'formatters' => ['plain'],
    ]),
    'Field descriptors must require an explicit label key.',
);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataContractDescriptor(
        'sample-owner.employment_contract.compensation_terms',
        1,
        [],
    ),
    'Semantic data contracts must name a non-empty field subset.',
);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataSourceDescriptor(
        appKey: 'sample-owner',
        sourceKey: 'sample-owner.invalid_contract_fields',
        sourceVersion: 1,
        resourceKey: 'sample-owner.employment_contract',
        supportedSubjectResourceKeys: ['sample-owner.employment_contract'],
        cardinality: SignatureDocumentDataCardinality::One,
        lookupMode: SignatureDocumentDataLookupMode::DerivedRef,
        providedDataContracts: [new SignatureDocumentDataContractDescriptor(
            'sample-owner.employment_contract.compensation_terms',
            1,
            ['undeclared'],
        )],
        fields: [$fullName],
        syntheticSample: ['worker_full_name' => '홍길동'],
        labelKey: 'sample-owner.signature_document_data.invalid.label',
        descriptionKey: 'sample-owner.signature_document_data.invalid.description',
        defaultSourceRefAnchor: 'worker',
    ),
    'A source must declare every field promised by a compatible data contract.',
);

$dateTimeField = documentDataField(
    'effective_at',
    SignatureDocumentDataFieldType::DateTime,
    formatters: [SignatureDocumentDataFormatter::DateTimeLocal],
);
if (! $dateTimeField->accepts('2026-02-28T23:59:59.123+09:00')
    || $dateTimeField->accepts('2026-02-30T23:59:59+09:00')
    || $dateTimeField->accepts('2026-02-28T25:00:00+09:00')) {
    throw new RuntimeException('Datetime values must be exact ISO instants without parser normalization.');
}

$leaf = documentDataField('leaf');
$depthThree = documentDataField(
    'level_two',
    SignatureDocumentDataFieldType::Object,
    formatters: [],
    schema: [$leaf],
);
$depthTwo = documentDataField(
    'level_one',
    SignatureDocumentDataFieldType::Object,
    formatters: [],
    schema: [$depthThree],
);
documentDataMustThrow(
    static fn () => documentDataField(
        'too_deep',
        SignatureDocumentDataFieldType::Object,
        formatters: [],
        schema: [$depthTwo],
    ),
    'Structured field schemas must reject depth greater than three.',
);

$wideSchema = [];
for ($index = 0; $index <= SignatureDocumentDataLimits::MAX_TOP_LEVEL_FIELDS; $index++) {
    $wideSchema[] = documentDataField('field_'.$index, required: false);
}
documentDataMustThrow(
    static fn () => documentDataField(
        'too_wide',
        SignatureDocumentDataFieldType::Object,
        formatters: [],
        schema: $wideSchema,
    ),
    'Structured field schemas must reject excessive width.',
);

$recursiveFields = [];
for ($group = 0; $group < 5; $group++) {
    $children = [];
    for ($index = 0; $index < 40; $index++) {
        $children[] = documentDataField("child_{$group}_{$index}", required: false);
    }
    $recursiveFields[] = documentDataField(
        'group_'.$group,
        SignatureDocumentDataFieldType::Object,
        required: false,
        formatters: [],
        schema: $children,
    );
}
documentDataMustThrow(
    static fn () => new SignatureDocumentDataSourceDescriptor(
        appKey: 'sample-owner',
        sourceKey: 'sample-owner.too_many_recursive_fields',
        sourceVersion: 1,
        resourceKey: 'sample-owner.employment_contract',
        supportedSubjectResourceKeys: ['sample-owner.employment_contract'],
        cardinality: SignatureDocumentDataCardinality::One,
        lookupMode: SignatureDocumentDataLookupMode::DerivedRef,
        providedDataContracts: [],
        fields: $recursiveFields,
        syntheticSample: [],
        labelKey: 'sample-owner.signature_document_data.too_many.label',
        descriptionKey: 'sample-owner.signature_document_data.too_many.description',
        defaultSourceRefAnchor: 'worker',
    ),
    'A source descriptor must reject excessive recursive field count.',
);

$optionalSort = documentDataField('sort_key', required: false);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataSourceDescriptor(
        appKey: 'sample-catalog',
        sourceKey: 'sample-catalog.active_assignments',
        sourceVersion: 1,
        resourceKey: 'sample-catalog.asset_assignment',
        supportedSubjectResourceKeys: ['sample-owner.employment_contract'],
        cardinality: SignatureDocumentDataCardinality::Many,
        lookupMode: SignatureDocumentDataLookupMode::DerivedRef,
        providedDataContracts: [],
        fields: [$optionalSort],
        syntheticSample: [],
        labelKey: 'sample-catalog.signature_document_data.active_assignments.label',
        descriptionKey: 'sample-catalog.signature_document_data.active_assignments.description',
        stableSortKey: 'sort_key',
        defaultSourceRefAnchor: 'worker',
    ),
    'Many-cardinality sources must use a required stable sort field.',
);

documentDataMustThrow(
    static fn () => new SignatureDocumentDataSourceDescriptor(
        appKey: 'sample-evaluation',
        sourceKey: 'sample-evaluation.approved_terms',
        sourceVersion: 1,
        resourceKey: 'sample-evaluation.compensation_change',
        supportedSubjectResourceKeys: ['sample-owner.employment_contract'],
        cardinality: SignatureDocumentDataCardinality::One,
        lookupMode: SignatureDocumentDataLookupMode::ExplicitSourceRef,
        providedDataContracts: [],
        fields: [$fullName],
        syntheticSample: ['worker_full_name' => '홍길동'],
        labelKey: 'sample-evaluation.signature_document_data.approved_terms.label',
        descriptionKey: 'sample-evaluation.signature_document_data.approved_terms.description',
    ),
    'Reference-based sources must explicitly declare their authoring anchor.',
);

$largeValues = [];
for ($index = 0; $index < 5; $index++) {
    $largeValues['text_'.$index] = str_repeat('x', 15000);
}
$largeOne = new SignatureDocumentDataResolvedItem(
    new ResourceRef('sample-owner', 'sample-owner.worker', 'large-1', 'Large 1'),
    'revision-1',
    null,
    null,
    $largeValues,
);
$largeTwo = new SignatureDocumentDataResolvedItem(
    new ResourceRef('sample-owner', 'sample-owner.worker', 'large-2', 'Large 2'),
    'revision-2',
    null,
    null,
    $largeValues,
);
documentDataMustThrow(
    static fn () => new SignatureDocumentDataResult(
        SignatureDocumentDataStatus::Resolved,
        resolvedItems: [$largeOne, $largeTwo],
    ),
    'The complete provider result must fit the 128 KiB source boundary.',
);

$resourceBackedProvider = new class(appKey: 'sample-owner', sourceKey: 'sample-owner.worker_employment', sourceVersion: 1, cardinality: SignatureDocumentDataCardinality::One, declaredProtectedFieldKeys: ['worker_full_name'], maximumResultCount: 1) extends AbstractResourceBackedSignatureDocumentDataSourceProvider
{
    /** @var list<string> */
    public array $checks = [];

    protected function assertActorAuthorized(SignatureDocumentDataQuery $query): void
    {
        $this->checks[] = 'actor';
    }

    protected function assertTenantAndLegalEntityScope(SignatureDocumentDataQuery $query): void
    {
        $this->checks[] = 'scope';
    }

    protected function assertSubjectRelationship(SignatureDocumentDataQuery $query): void
    {
        $this->checks[] = 'subject';
    }

    protected function assertAsOfAndState(SignatureDocumentDataQuery $query): void
    {
        $this->checks[] = 'as_of';
    }

    protected function resolveBoundedResources(
        SignatureDocumentDataQuery $query,
        int $maximumResultCount,
    ): array {
        $this->checks[] = 'lookup:'.$maximumResultCount;

        return [new ResourceBackedSignatureDocumentData(
            resourceRef: $query->derivedSubjectAnchors['worker'],
            sourceRevision: 'worker-profile-7',
            contentHash: str_repeat('a', 64),
            effectiveAt: $query->asOf,
            protectedValues: ['worker_full_name' => '홍길동'],
        )];
    }
};
$resourceBackedQuery = new SignatureDocumentDataQuery(
    tenant: $tenant,
    legalEntity: $legalEntity,
    actor: $actor,
    purpose: SignatureDocumentDataPurpose::SignatureRequestPreparation,
    appKey: 'sample-owner',
    sourceKey: 'sample-owner.worker_employment',
    sourceVersion: 1,
    subjectResourceRef: $subject,
    derivedSubjectAnchors: ['worker' => $worker],
    explicitSourceRef: null,
    asOf: new DateTimeImmutable('2026-09-01T00:00:00+09:00'),
    requestedFieldKeys: ['worker_full_name'],
    selectedResourceRefs: [$worker],
);
$resourceBackedResult = $resourceBackedProvider->resolve($resourceBackedQuery);
if ($resourceBackedProvider->checks !== ['actor', 'scope', 'subject', 'as_of', 'lookup:1']
    || $resourceBackedResult->resolvedItems()[0]->protectedValues() !== ['worker_full_name' => '홍길동']) {
    throw new RuntimeException('The resource-backed helper must run every explicit App-owned guard before its bounded lookup.');
}

documentDataMustThrow(
    static fn () => new class(appKey: 'sample-owner', sourceKey: 'sample-owner.worker_employment', sourceVersion: 1, cardinality: SignatureDocumentDataCardinality::Many, declaredProtectedFieldKeys: ['worker_full_name'], maximumResultCount: SignatureDocumentDataLimits::MAX_LIST_ITEMS + 1) extends AbstractResourceBackedSignatureDocumentDataSourceProvider
    {
        protected function assertActorAuthorized(SignatureDocumentDataQuery $query): void {}

        protected function assertTenantAndLegalEntityScope(SignatureDocumentDataQuery $query): void {}

        protected function assertSubjectRelationship(SignatureDocumentDataQuery $query): void {}

        protected function assertAsOfAndState(SignatureDocumentDataQuery $query): void {}

        protected function resolveBoundedResources(SignatureDocumentDataQuery $query, int $maximumResultCount): array
        {
            return [];
        }
    },
    'The resource-backed helper must reject an unbounded result limit.',
);

$wrongSelection = new SignatureDocumentDataQuery(
    tenant: $tenant,
    legalEntity: $legalEntity,
    actor: $actor,
    purpose: SignatureDocumentDataPurpose::SignatureRequestPreparation,
    appKey: 'sample-owner',
    sourceKey: 'sample-owner.worker_employment',
    sourceVersion: 1,
    subjectResourceRef: $subject,
    derivedSubjectAnchors: ['worker' => $worker],
    explicitSourceRef: null,
    asOf: new DateTimeImmutable('2026-09-01T00:00:00+09:00'),
    requestedFieldKeys: ['worker_full_name'],
    selectedResourceRefs: [new ResourceRef('sample-owner', 'sample-owner.worker', 'worker-2', 'Worker 2')],
);
documentDataMustThrow(
    static fn () => $resourceBackedProvider->resolve($wrongSelection),
    'The resource-backed helper must exactly revalidate selected ResourceRefs.',
);

fwrite(STDOUT, "Signature document data source contracts passed.\n");
