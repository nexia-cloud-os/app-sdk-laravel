<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\SignatureDataClassification;
use Nexia\Signature\SignatureDocumentDataFieldType;
use Nexia\Signature\SignatureDocumentDataFormatter;
use Nexia\Signature\SignatureDocumentDataLimits;

/**
 * Closed, bounded schema for one protected value that an App may make
 * available only through signature request preparation.
 */
final readonly class SignatureDocumentDataFieldDescriptor
{
    private const KEY_PATTERN = '/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*\z/D';

    /**
     * For object and list fields, schema is an ordered list of the fields in
     * each object value or list item. List values are therefore always bounded
     * lists of typed objects in v1, rather than arbitrary JSON arrays.
     *
     * @param  list<SignatureDocumentDataFormatter>  $formatters
     * @param  list<SignatureDocumentDataFieldDescriptor>  $schema
     */
    public function __construct(
        public string $key,
        public SignatureDocumentDataFieldType $type,
        public bool $required,
        public SignatureDataClassification $classification,
        public array $formatters,
        public string $labelKey,
        public array $schema = [],
    ) {
        if (! self::isCanonicalKey($key)) {
            throw new InvalidArgumentException("Signature document data field key [{$key}] must be canonical lower snake-case segments.");
        }

        if ($labelKey === '' || $labelKey !== trim($labelKey)) {
            throw new InvalidArgumentException("Signature document data field [{$key}] label_key must be normalized and non-blank.");
        }
        SignatureDocumentDataLimits::assertStringLength($labelKey, SignatureDocumentDataLimits::MAX_STRING_CHARACTERS, "Signature document data field [{$key}] label_key");

        if (! array_is_list($formatters) || ! array_is_list($schema)) {
            throw new InvalidArgumentException("Signature document data field [{$key}] formatters and schema must be lists.");
        }

        $formatterValues = [];
        foreach ($formatters as $formatter) {
            if (! $formatter instanceof SignatureDocumentDataFormatter || isset($formatterValues[$formatter->value])) {
                throw new InvalidArgumentException("Signature document data field [{$key}] formatters must be unique supported formatter values.");
            }
            $formatterValues[$formatter->value] = true;
        }

        $isStructured = in_array($type, [SignatureDocumentDataFieldType::Object, SignatureDocumentDataFieldType::List], true);
        if ($isStructured !== ($schema !== [])) {
            throw new InvalidArgumentException("Signature document data field [{$key}] must declare schema only for object or list values.");
        }

        $schemaKeys = [];
        foreach ($schema as $field) {
            if (! $field instanceof self || isset($schemaKeys[$field->key])) {
                throw new InvalidArgumentException("Signature document data field [{$key}] schema must contain uniquely keyed field descriptors.");
            }
            $schemaKeys[$field->key] = true;
        }
        if (count($schema) > SignatureDocumentDataLimits::MAX_TOP_LEVEL_FIELDS) {
            throw new InvalidArgumentException("Signature document data field [{$key}] schema exceeds the maximum width.");
        }

        self::assertSchemaDepth($this, 1);
        if (self::recursiveFieldCount($this) > SignatureDocumentDataLimits::MAX_MAPPED_VALUES) {
            throw new InvalidArgumentException("Signature document data field [{$key}] schema exceeds the maximum recursive field count.");
        }
        self::assertFormatterCompatibility($key, $type, $formatters);
        SignatureDocumentDataLimits::assertPayloadByteLength(
            $this->toArray(),
            SignatureDocumentDataLimits::MAX_SOURCE_RESULT_BYTES,
            "Signature document data field [{$key}] descriptor",
        );
    }

    public static function isCanonicalKey(string $key): bool
    {
        return preg_match(self::KEY_PATTERN, $key) === 1;
    }

    public function accepts(mixed $value): bool
    {
        if ($value === null) {
            return ! $this->required;
        }

        try {
            $this->assertAccepts($value);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function assertAccepts(mixed $value): void
    {
        if ($value === null) {
            if ($this->required) {
                throw new InvalidArgumentException("Signature document data field [{$this->key}] is required.");
            }

            return;
        }

        match ($this->type) {
            SignatureDocumentDataFieldType::String => $this->assertString($value, SignatureDocumentDataLimits::MAX_STRING_CHARACTERS),
            SignatureDocumentDataFieldType::Text => $this->assertString($value, SignatureDocumentDataLimits::MAX_TEXT_CHARACTERS),
            SignatureDocumentDataFieldType::Date => $this->assertDate($value),
            SignatureDocumentDataFieldType::DateTime => $this->assertDateTime($value),
            SignatureDocumentDataFieldType::Boolean => $this->assertBoolean($value),
            SignatureDocumentDataFieldType::Integer => $this->assertInteger($value),
            SignatureDocumentDataFieldType::Decimal => $this->assertDecimal($value),
            SignatureDocumentDataFieldType::Money => $this->assertMoney($value),
            SignatureDocumentDataFieldType::ResourceRef => $this->assertResourceRef($value),
            SignatureDocumentDataFieldType::Object => $this->assertObject($value),
            SignatureDocumentDataFieldType::List => $this->assertList($value),
        };
    }

    /**
     * @return array{key: string, type: string, required: bool, classification: string, formatters: list<string>, schema?: list<array<string, mixed>>, label_key: string}
     */
    public function toArray(): array
    {
        $data = [
            'key' => $this->key,
            'type' => $this->type->value,
            'required' => $this->required,
            'classification' => $this->classification->value,
            'formatters' => array_map(
                static fn (SignatureDocumentDataFormatter $formatter): string => $formatter->value,
                $this->formatters,
            ),
        ];

        if ($this->schema !== []) {
            $data['schema'] = array_map(
                static fn (self $field): array => $field->toArray(),
                $this->schema,
            );
        }

        $data['label_key'] = $this->labelKey;

        return $data;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $allowed = ['key', 'type', 'required', 'classification', 'formatters', 'schema', 'label_key'];
        if (array_is_list($data) || array_diff(array_keys($data), $allowed) !== []) {
            throw new InvalidArgumentException('Serialized signature document data field shape is invalid.');
        }

        $formatters = $data['formatters'] ?? null;
        $schema = $data['schema'] ?? [];
        if (! is_array($formatters) || ! array_is_list($formatters) || ! is_array($schema) || ! array_is_list($schema)) {
            throw new InvalidArgumentException('Serialized signature document data field formatters or schema are invalid.');
        }

        return new self(
            key: self::requiredString($data, 'key'),
            type: SignatureDocumentDataFieldType::from(self::requiredString($data, 'type')),
            required: self::requiredBoolean($data, 'required'),
            classification: SignatureDataClassification::from(self::requiredString($data, 'classification')),
            formatters: array_map(
                static fn (mixed $formatter): SignatureDocumentDataFormatter => SignatureDocumentDataFormatter::from(
                    is_string($formatter) ? $formatter : '',
                ),
                $formatters,
            ),
            labelKey: self::requiredString($data, 'label_key'),
            schema: array_map(
                static fn (mixed $field): self => self::fromArray(
                    is_array($field) ? $field : throw new InvalidArgumentException('Serialized signature document data schema fields must be objects.'),
                ),
                $schema,
            ),
        );
    }

    private static function assertSchemaDepth(self $field, int $depth): void
    {
        if ($depth > SignatureDocumentDataLimits::MAX_NESTING_DEPTH) {
            throw new InvalidArgumentException("Signature document data field [{$field->key}] exceeds the maximum schema depth.");
        }

        foreach ($field->schema as $child) {
            self::assertSchemaDepth($child, $depth + 1);
        }
    }

    private static function recursiveFieldCount(self $field): int
    {
        $count = 1;
        foreach ($field->schema as $child) {
            $count += self::recursiveFieldCount($child);
        }

        return $count;
    }

    /** @param list<SignatureDocumentDataFormatter> $formatters */
    private static function assertFormatterCompatibility(
        string $key,
        SignatureDocumentDataFieldType $type,
        array $formatters,
    ): void {
        $allowed = match ($type) {
            SignatureDocumentDataFieldType::String,
            SignatureDocumentDataFieldType::Text => [SignatureDocumentDataFormatter::Plain],
            SignatureDocumentDataFieldType::Date => [
                SignatureDocumentDataFormatter::DateIso,
                SignatureDocumentDataFormatter::DateLocal,
            ],
            SignatureDocumentDataFieldType::DateTime => [SignatureDocumentDataFormatter::DateTimeLocal],
            SignatureDocumentDataFieldType::Boolean => [SignatureDocumentDataFormatter::BooleanYesNo],
            SignatureDocumentDataFieldType::Integer => [SignatureDocumentDataFormatter::Integer],
            SignatureDocumentDataFieldType::Decimal => [SignatureDocumentDataFormatter::Decimal],
            SignatureDocumentDataFieldType::Money => [SignatureDocumentDataFormatter::MoneyWithCurrency],
            SignatureDocumentDataFieldType::ResourceRef => [SignatureDocumentDataFormatter::ResourceLabel],
            SignatureDocumentDataFieldType::Object,
            SignatureDocumentDataFieldType::List => [],
        };

        foreach ($formatters as $formatter) {
            if (! in_array($formatter, $allowed, true)) {
                throw new InvalidArgumentException("Signature document data field [{$key}] formatter [{$formatter->value}] is incompatible with type [{$type->value}].");
            }
        }
    }

    private function assertString(mixed $value, int $maximum): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be a string.");
        }

        SignatureDocumentDataLimits::assertStringLength($value, $maximum, "Signature document data field [{$this->key}]");
    }

    private function assertDate(mixed $value): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be an ISO date.");
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be an ISO date.");
        }
    }

    private function assertDateTime(mixed $value): void
    {
        if (! is_string($value)
            || preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $value) !== 1) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be an ISO datetime with an offset.");
        }

        preg_match('/\A(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})\z/D', $value, $parts);
        $fraction = $parts[3] ?? '';
        $offset = ($parts[4] ?? '') === 'Z' ? '+00:00' : ($parts[4] ?? '');
        $format = $fraction === '' ? '!Y-m-d\\TH:i:sP' : '!Y-m-d\\TH:i:s.uP';
        $normalized = ($parts[1] ?? '').'T'.($parts[2] ?? '')
            .($fraction === '' ? '' : '.'.str_pad(substr($fraction, 1), 6, '0'))
            .$offset;
        $date = DateTimeImmutable::createFromFormat($format, $normalized);
        $errors = DateTimeImmutable::getLastErrors();
        if (! $date instanceof DateTimeImmutable
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format($fraction === '' ? 'Y-m-d\\TH:i:sP' : 'Y-m-d\\TH:i:s.uP') !== $normalized) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be an ISO datetime with an offset.");
        }
    }

    private function assertBoolean(mixed $value): void
    {
        if (! is_bool($value)) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be a boolean.");
        }
    }

    private function assertInteger(mixed $value): void
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be an integer.");
        }
    }

    private function assertDecimal(mixed $value): void
    {
        if (is_int($value)) {
            return;
        }

        if (is_float($value) && is_finite($value)) {
            return;
        }

        if (! is_string($value) || preg_match('/\A-?(?:0|[1-9]\d*)(?:\.\d+)?\z/D', $value) !== 1) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be a canonical decimal.");
        }
    }

    private function assertMoney(mixed $value): void
    {
        if (! is_array($value) || array_is_list($value) || array_diff(array_keys($value), ['amount', 'currency']) !== [] || count($value) !== 2) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be a money object.");
        }

        $this->assertDecimal($value['amount'] ?? null);
        if (! is_string($value['currency'] ?? null) || preg_match('/\A[A-Z]{3}\z/D', $value['currency']) !== 1) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] money currency must be an ISO code.");
        }
    }

    private function assertResourceRef(mixed $value): void
    {
        if ($value instanceof ResourceRef) {
            return;
        }

        if (! is_array($value) || array_is_list($value)
            || array_diff(array_keys($value), ['app_key', 'resource_key', 'resource_id', 'display', 'href']) !== []
            || ! is_string($value['app_key'] ?? null)
            || ! is_string($value['resource_key'] ?? null)
            || ! is_string($value['resource_id'] ?? null)
            || ! is_string($value['display'] ?? null)
            || (array_key_exists('href', $value) && ! is_string($value['href']) && $value['href'] !== null)) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be a canonical resource reference.");
        }

        ResourceRef::fromArray($value);
    }

    private function assertObject(mixed $value): void
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be an object.");
        }

        $this->assertStructuredObject($value);
    }

    private function assertList(mixed $value): void
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > SignatureDocumentDataLimits::MAX_LIST_ITEMS) {
            throw new InvalidArgumentException("Signature document data field [{$this->key}] must be a bounded list.");
        }

        foreach ($value as $item) {
            if (! is_array($item) || ($item !== [] && array_is_list($item))) {
                throw new InvalidArgumentException("Signature document data field [{$this->key}] list items must be objects.");
            }
            $this->assertStructuredObject($item);
        }
    }

    /** @param array<string, mixed> $value */
    private function assertStructuredObject(array $value): void
    {
        $fields = [];
        foreach ($this->schema as $field) {
            $fields[$field->key] = $field;
        }

        foreach ($value as $key => $child) {
            if (! is_string($key) || ! isset($fields[$key])) {
                throw new InvalidArgumentException("Signature document data field [{$this->key}] contains an undeclared nested field.");
            }
            $fields[$key]->assertAccepts($child);
        }

        foreach ($fields as $field) {
            if ($field->required && ! array_key_exists($field->key, $value)) {
                throw new InvalidArgumentException("Signature document data field [{$this->key}] is missing required nested field [{$field->key}].");
            }
        }
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        if (! is_string($data[$key] ?? null)) {
            throw new InvalidArgumentException("Serialized signature document data field [{$key}] must be a string.");
        }

        return $data[$key];
    }

    /** @param array<string, mixed> $data */
    private static function requiredBoolean(array $data, string $key): bool
    {
        if (! is_bool($data[$key] ?? null)) {
            throw new InvalidArgumentException("Serialized signature document data field [{$key}] must be a boolean.");
        }

        return $data[$key];
    }
}
