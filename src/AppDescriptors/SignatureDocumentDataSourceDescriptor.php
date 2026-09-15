<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\SignatureDocumentDataCardinality;
use Nexia\Signature\SignatureDocumentDataFieldType;
use Nexia\Signature\SignatureDocumentDataLimits;
use Nexia\Signature\SignatureDocumentDataLookupMode;

/**
 * Immutable catalog metadata for one App-owned protected signature data
 * source. Actual values remain available only through the paired runtime
 * provider during signature request preparation.
 */
final readonly class SignatureDocumentDataSourceDescriptor implements AppDescriptor
{
    /** Canonical descriptor identity: `{source_key}@{source_version}`. */
    public string $key;

    /**
     * @param  non-empty-list<string>  $supportedSubjectResourceKeys
     * @param  list<SignatureDocumentDataContractDescriptor>  $providedDataContracts
     * @param  non-empty-list<SignatureDocumentDataFieldDescriptor>  $fields
     * @param  array<string, mixed>  $syntheticSample
     */
    public function __construct(
        public string $appKey,
        public string $sourceKey,
        public int $sourceVersion,
        public array $supportedSubjectResourceKeys,
        public SignatureDocumentDataCardinality $cardinality,
        public SignatureDocumentDataLookupMode $lookupMode,
        public array $providedDataContracts,
        public array $fields,
        public array $syntheticSample,
        public string $labelKey,
        public string $descriptionKey,
        public ?string $stableSortKey = null,
        public ?string $defaultSourceRefAnchor = null,
        public ?string $resourceKey = null,
    ) {
        if (! preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)*\z/D', $appKey)) {
            throw new InvalidArgumentException('Signature document data source app_key must be canonical.');
        }

        if (! ResourceRef::hasCanonicalIdentity($appKey, $sourceKey)) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] must be owned by app [{$appKey}].");
        }

        if ($sourceVersion < 1) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] version must be positive.");
        }

        if ($resourceKey !== null && ! ResourceRef::hasCanonicalIdentity($appKey, $resourceKey)) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] resource [{$resourceKey}] must be owned by app [{$appKey}].");
        }

        if (! array_is_list($supportedSubjectResourceKeys) || $supportedSubjectResourceKeys === []) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] must declare supported subject resource keys.");
        }
        $subjectKeys = [];
        foreach ($supportedSubjectResourceKeys as $resourceKey) {
            $subjectAppKey = is_string($resourceKey) ? explode('.', $resourceKey, 2)[0] : '';
            if (! is_string($resourceKey)
                || ! ResourceRef::hasCanonicalIdentity($subjectAppKey, $resourceKey)
                || isset($subjectKeys[$resourceKey])) {
                throw new InvalidArgumentException("Signature document data source [{$sourceKey}] subject resource keys must be unique canonical resource keys.");
            }
            $subjectKeys[$resourceKey] = true;
        }

        if (! array_is_list($providedDataContracts)) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] provided data contracts must be a list.");
        }
        $contracts = [];
        foreach ($providedDataContracts as $contract) {
            if (! $contract instanceof SignatureDocumentDataContractDescriptor || isset($contracts[$contract->identity()])) {
                throw new InvalidArgumentException("Signature document data source [{$sourceKey}] provided data contracts must be uniquely versioned descriptors.");
            }
            $contracts[$contract->identity()] = true;
        }

        $needsSourceRefAnchor = in_array($lookupMode, [
            SignatureDocumentDataLookupMode::DerivedRef,
            SignatureDocumentDataLookupMode::ExplicitSourceRef,
        ], true);
        if ($needsSourceRefAnchor
            && (! is_string($defaultSourceRefAnchor)
                || preg_match('/\A[a-z][a-z0-9_]*\z/D', $defaultSourceRefAnchor) !== 1)) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] must declare a canonical default_source_ref_anchor for its lookup mode.");
        }
        if (! $needsSourceRefAnchor && $defaultSourceRefAnchor !== null) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] cannot declare default_source_ref_anchor for its lookup mode.");
        }

        if (! array_is_list($fields) || $fields === [] || count($fields) > SignatureDocumentDataLimits::MAX_TOP_LEVEL_FIELDS) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] must declare 1 to ".SignatureDocumentDataLimits::MAX_TOP_LEVEL_FIELDS.' top-level fields.');
        }
        $fieldMap = [];
        foreach ($fields as $field) {
            if (! $field instanceof SignatureDocumentDataFieldDescriptor || isset($fieldMap[$field->key])) {
                throw new InvalidArgumentException("Signature document data source [{$sourceKey}] fields must be uniquely keyed descriptors.");
            }
            $fieldMap[$field->key] = $field;
        }
        foreach ($providedDataContracts as $contract) {
            foreach ($contract->fieldKeys as $fieldKey) {
                if (! isset($fieldMap[$fieldKey])) {
                    throw new InvalidArgumentException("Signature document data source [{$sourceKey}] contract [{$contract->identity()}] declares unavailable field [{$fieldKey}].");
                }
            }
        }

        if (($cardinality === SignatureDocumentDataCardinality::Many && $stableSortKey === null)
            || ($cardinality !== SignatureDocumentDataCardinality::Many && $stableSortKey !== null)) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] must declare stable_sort_key exactly for many-cardinality sources.");
        }
        if ($stableSortKey !== null
            && (! SignatureDocumentDataFieldDescriptor::isCanonicalKey($stableSortKey) || ! isset($fieldMap[$stableSortKey]))) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] stable_sort_key must name a declared canonical field.");
        }
        if ($stableSortKey !== null
            && (! $fieldMap[$stableSortKey]->required
                || ! in_array($fieldMap[$stableSortKey]->type, [
                    SignatureDocumentDataFieldType::String,
                    SignatureDocumentDataFieldType::Date,
                    SignatureDocumentDataFieldType::DateTime,
                    SignatureDocumentDataFieldType::Integer,
                    SignatureDocumentDataFieldType::Decimal,
                ], true))) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] stable_sort_key must name a required deterministically orderable scalar field.");
        }

        foreach (['label_key' => $labelKey, 'description_key' => $descriptionKey] as $name => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature document data source [{$sourceKey}] {$name} must be normalized and non-blank.");
            }
            SignatureDocumentDataLimits::assertStringLength($value, SignatureDocumentDataLimits::MAX_STRING_CHARACTERS, "Signature document data source [{$sourceKey}] {$name}");
        }

        if ($syntheticSample !== [] && array_is_list($syntheticSample)) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] synthetic_sample must be an object.");
        }
        foreach ($syntheticSample as $fieldKey => $value) {
            if (! is_string($fieldKey) || ! isset($fieldMap[$fieldKey])) {
                throw new InvalidArgumentException("Signature document data source [{$sourceKey}] synthetic_sample contains an undeclared field.");
            }
            $fieldMap[$fieldKey]->assertAccepts($value);
        }
        foreach ($fieldMap as $field) {
            if ($field->required && ! array_key_exists($field->key, $syntheticSample)) {
                throw new InvalidArgumentException("Signature document data source [{$sourceKey}] synthetic_sample is missing required field [{$field->key}].");
            }
        }
        SignatureDocumentDataLimits::assertBoundedJsonValue($syntheticSample, "Signature document data source [{$sourceKey}] synthetic_sample");
        SignatureDocumentDataLimits::assertPayloadByteLength(
            $syntheticSample,
            SignatureDocumentDataLimits::MAX_SOURCE_RESULT_BYTES,
            "Signature document data source [{$sourceKey}] synthetic_sample",
        );
        if (self::recursiveFieldCount($fields) > SignatureDocumentDataLimits::MAX_MAPPED_VALUES) {
            throw new InvalidArgumentException("Signature document data source [{$sourceKey}] exceeds the maximum recursive field count.");
        }
        SignatureDocumentDataLimits::assertPayloadByteLength(
            $this->toArray(),
            SignatureDocumentDataLimits::MAX_SOURCE_RESULT_BYTES,
            "Signature document data source [{$sourceKey}] descriptor",
        );

        $this->key = $sourceKey.'@'.$sourceVersion;
    }

    /**
     * @return array{
     *   app_key: string,
     *   source_key: string,
     *   source_version: int,
     *   resource_key?: string,
     *   supported_subject_resource_keys: non-empty-list<string>,
     *   cardinality: string,
     *   lookup_mode: string,
     *   provided_data_contracts: list<array{data_contract_key: string, data_contract_version: int, field_keys: non-empty-list<string>}>,
     *   fields: non-empty-list<array<string, mixed>>,
     *   synthetic_sample: array<string, mixed>,
     *   stable_sort_key?: string,
     *   default_source_ref_anchor?: string,
     *   label_key: string,
     *   description_key: string
     * }
     */
    public function toArray(): array
    {
        $data = [
            'app_key' => $this->appKey,
            'source_key' => $this->sourceKey,
            'source_version' => $this->sourceVersion,
            'supported_subject_resource_keys' => $this->supportedSubjectResourceKeys,
            'cardinality' => $this->cardinality->value,
            'lookup_mode' => $this->lookupMode->value,
            'provided_data_contracts' => array_map(
                static fn (SignatureDocumentDataContractDescriptor $contract): array => $contract->toArray(),
                $this->providedDataContracts,
            ),
            'fields' => array_map(
                static fn (SignatureDocumentDataFieldDescriptor $field): array => $field->toArray(),
                $this->fields,
            ),
            'synthetic_sample' => $this->syntheticSample,
        ];

        if ($this->resourceKey !== null) {
            $data['resource_key'] = $this->resourceKey;
        }

        if ($this->stableSortKey !== null) {
            $data['stable_sort_key'] = $this->stableSortKey;
        }
        if ($this->defaultSourceRefAnchor !== null) {
            $data['default_source_ref_anchor'] = $this->defaultSourceRefAnchor;
        }
        $data['label_key'] = $this->labelKey;
        $data['description_key'] = $this->descriptionKey;

        return $data;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $required = [
            'app_key',
            'source_key',
            'source_version',
            'supported_subject_resource_keys',
            'cardinality',
            'lookup_mode',
            'provided_data_contracts',
            'fields',
            'synthetic_sample',
            'label_key',
            'description_key',
        ];
        $optional = ['resource_key', 'stable_sort_key', 'default_source_ref_anchor'];
        if (array_is_list($data)
            || array_diff(array_keys($data), [...$required, ...$optional]) !== []
            || array_diff($required, array_keys($data)) !== []) {
            throw new InvalidArgumentException('Serialized signature document data source shape is invalid.');
        }

        $subjectResourceKeys = $data['supported_subject_resource_keys'];
        $providedDataContracts = $data['provided_data_contracts'];
        $fields = $data['fields'];
        $syntheticSample = $data['synthetic_sample'];
        if (! is_array($subjectResourceKeys) || ! array_is_list($subjectResourceKeys)
            || ! is_array($providedDataContracts) || ! array_is_list($providedDataContracts)
            || ! is_array($fields) || ! array_is_list($fields)
            || ! is_array($syntheticSample) || ($syntheticSample !== [] && array_is_list($syntheticSample))) {
            throw new InvalidArgumentException('Serialized signature document data source collection shape is invalid.');
        }

        return new self(
            appKey: self::requiredString($data, 'app_key'),
            sourceKey: self::requiredString($data, 'source_key'),
            sourceVersion: self::requiredInteger($data, 'source_version'),
            supportedSubjectResourceKeys: array_map(
                static fn (mixed $resourceKey): string => is_string($resourceKey)
                    ? $resourceKey
                    : throw new InvalidArgumentException('Serialized signature document data subject resource keys must be strings.'),
                $subjectResourceKeys,
            ),
            cardinality: SignatureDocumentDataCardinality::from(self::requiredString($data, 'cardinality')),
            lookupMode: SignatureDocumentDataLookupMode::from(self::requiredString($data, 'lookup_mode')),
            providedDataContracts: array_map(
                static fn (mixed $contract): SignatureDocumentDataContractDescriptor => SignatureDocumentDataContractDescriptor::fromArray(
                    is_array($contract)
                        ? $contract
                        : throw new InvalidArgumentException('Serialized signature document data contracts must be objects.'),
                ),
                $providedDataContracts,
            ),
            fields: array_map(
                static fn (mixed $field): SignatureDocumentDataFieldDescriptor => SignatureDocumentDataFieldDescriptor::fromArray(
                    is_array($field)
                        ? $field
                        : throw new InvalidArgumentException('Serialized signature document data fields must be objects.'),
                ),
                $fields,
            ),
            syntheticSample: $syntheticSample,
            labelKey: self::requiredString($data, 'label_key'),
            descriptionKey: self::requiredString($data, 'description_key'),
            stableSortKey: self::nullableString($data, 'stable_sort_key'),
            defaultSourceRefAnchor: self::nullableString($data, 'default_source_ref_anchor'),
            resourceKey: self::nullableString($data, 'resource_key'),
        );
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        if (! is_string($data[$key] ?? null)) {
            throw new InvalidArgumentException("Serialized signature document data source [{$key}] must be a string.");
        }

        return $data[$key];
    }

    /** @param array<string, mixed> $data */
    private static function requiredInteger(array $data, string $key): int
    {
        if (! is_int($data[$key] ?? null)) {
            throw new InvalidArgumentException("Serialized signature document data source [{$key}] must be an integer.");
        }

        return $data[$key];
    }

    /** @param array<string, mixed> $data */
    private static function nullableString(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }
        if (! is_string($data[$key])) {
            throw new InvalidArgumentException("Serialized signature document data source [{$key}] must be a string when present.");
        }

        return $data[$key];
    }

    /** @param list<SignatureDocumentDataFieldDescriptor> $fields */
    private static function recursiveFieldCount(array $fields): int
    {
        $count = 0;
        foreach ($fields as $field) {
            $count++;
            $count += self::recursiveFieldCount($field->schema);
        }

        return $count;
    }
}
