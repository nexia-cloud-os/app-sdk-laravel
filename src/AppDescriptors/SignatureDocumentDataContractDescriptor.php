<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\Signature\SignatureDocumentDataLimits;

/**
 * A versioned semantic output contract that one or more source descriptors
 * can provide without exposing their owner App's persistence model.
 */
final readonly class SignatureDocumentDataContractDescriptor
{
    public function __construct(
        public string $dataContractKey,
        public int $dataContractVersion,
        public array $fieldKeys,
    ) {
        if (! self::hasCanonicalKey($dataContractKey)) {
            throw new InvalidArgumentException('Signature document data contract key must be a canonical qualified key.');
        }

        if ($dataContractVersion < 1) {
            throw new InvalidArgumentException("Signature document data contract [{$dataContractKey}] version must be positive.");
        }

        if (! array_is_list($fieldKeys)
            || $fieldKeys === []
            || count($fieldKeys) > SignatureDocumentDataLimits::MAX_TOP_LEVEL_FIELDS) {
            throw new InvalidArgumentException("Signature document data contract [{$dataContractKey}] field_keys must be a bounded non-empty list.");
        }
        $seen = [];
        foreach ($fieldKeys as $fieldKey) {
            if (! is_string($fieldKey)
                || ! SignatureDocumentDataFieldDescriptor::isCanonicalKey($fieldKey)
                || isset($seen[$fieldKey])) {
                throw new InvalidArgumentException("Signature document data contract [{$dataContractKey}] field_keys must be unique canonical field keys.");
            }
            $seen[$fieldKey] = true;
        }
        SignatureDocumentDataLimits::assertPayloadByteLength(
            $this->toArray(),
            SignatureDocumentDataLimits::MAX_SOURCE_RESULT_BYTES,
            "Signature document data contract [{$dataContractKey}] descriptor",
        );
    }

    /** @return array{data_contract_key: string, data_contract_version: int, field_keys: non-empty-list<string>} */
    public function toArray(): array
    {
        return [
            'data_contract_key' => $this->dataContractKey,
            'data_contract_version' => $this->dataContractVersion,
            'field_keys' => $this->fieldKeys,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        self::assertKeys($data, ['data_contract_key', 'data_contract_version', 'field_keys']);

        $fieldKeys = $data['field_keys'] ?? null;
        if (! is_array($fieldKeys) || ! array_is_list($fieldKeys)) {
            throw new InvalidArgumentException('Serialized signature document data contract field_keys must be a list.');
        }

        return new self(
            dataContractKey: self::stringValue($data, 'data_contract_key'),
            dataContractVersion: self::integerValue($data, 'data_contract_version'),
            fieldKeys: array_map(
                static fn (mixed $fieldKey): string => is_string($fieldKey)
                    ? $fieldKey
                    : throw new InvalidArgumentException('Serialized signature document data contract field_keys must contain strings.'),
                $fieldKeys,
            ),
        );
    }

    public function identity(): string
    {
        return $this->dataContractKey.'@'.$this->dataContractVersion;
    }

    private static function hasCanonicalKey(string $key): bool
    {
        return preg_match('/\A[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*){1,7}\z/D', $key) === 1;
    }

    /** @param array<string, mixed> $data @param list<string> $allowed */
    private static function assertKeys(array $data, array $allowed): void
    {
        if (array_is_list($data) || array_diff(array_keys($data), $allowed) !== [] || count($data) !== count($allowed)) {
            throw new InvalidArgumentException('Serialized signature document data contract shape is invalid.');
        }
    }

    /** @param array<string, mixed> $data */
    private static function stringValue(array $data, string $key): string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : '';
    }

    /** @param array<string, mixed> $data */
    private static function integerValue(array $data, string $key): int
    {
        return is_int($data[$key] ?? null) ? $data[$key] : 0;
    }
}
