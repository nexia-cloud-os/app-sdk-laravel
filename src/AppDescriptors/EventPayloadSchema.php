<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Throwable;

/** Structure and value validation for the supported public Event payload schema. */
final class EventPayloadSchema
{
    private const TYPES = ['string', 'integer', 'number', 'boolean', 'array', 'object'];

    private const FORMATS = ['uuid', 'date', 'date-time'];

    private const NODE_KEYS = [
        'type',
        'nullable',
        'required',
        'format',
        'enum',
        'properties',
        'items',
        'labelKey',
        'label_key',
        'enum_labels',
    ];

    /** @param array<string, mixed> $schema */
    public static function assertValid(string $eventKey, array $schema): void
    {
        foreach ($schema as $field => $node) {
            self::assertFieldKey($eventKey, $field, null);
            self::assertNode($eventKey, $node, (string) $field);
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $payload
     */
    public static function assertPayload(string $eventKey, array $schema, array $payload): void
    {
        self::assertValid($eventKey, $schema);
        self::assertObjectValue($eventKey, $schema, $payload, null);
    }

    private static function assertNode(string $eventKey, mixed $node, string $path): void
    {
        if (! is_array($node) || array_is_list($node)) {
            throw DescriptorValidationException::eventPayload($eventKey, 'must be an object.', $path);
        }

        foreach (array_keys($node) as $keyword) {
            if (! is_string($keyword) || ! in_array($keyword, self::NODE_KEYS, true)) {
                throw DescriptorValidationException::eventPayload(
                    $eventKey,
                    "contains unsupported schema keyword [{$keyword}].",
                    $path,
                );
            }
        }

        $type = $node['type'] ?? null;

        if (! is_string($type) || ! in_array($type, self::TYPES, true)) {
            throw DescriptorValidationException::eventPayload(
                $eventKey,
                'must declare one supported type: string, integer, number, boolean, array, or object.',
                $path,
            );
        }

        foreach (['nullable', 'required'] as $booleanKeyword) {
            if (array_key_exists($booleanKeyword, $node) && ! is_bool($node[$booleanKeyword])) {
                throw DescriptorValidationException::eventPayload(
                    $eventKey,
                    "[{$booleanKeyword}] must be a boolean.",
                    $path,
                );
            }
        }

        if (array_key_exists('format', $node)) {
            $format = $node['format'];

            if ($type !== 'string' || ! is_string($format) || ! in_array($format, self::FORMATS, true)) {
                throw DescriptorValidationException::eventPayload(
                    $eventKey,
                    '[format] must be uuid, date, or date-time on a string field.',
                    $path,
                );
            }
        }

        if (array_key_exists('enum', $node)) {
            self::assertEnum($eventKey, $node, $type, $path);
        }

        if (array_key_exists('properties', $node)) {
            if ($type !== 'object' || ! is_array($node['properties']) || array_is_list($node['properties'])) {
                throw DescriptorValidationException::eventPayload(
                    $eventKey,
                    '[properties] must be an object declared only on an object field.',
                    $path,
                );
            }

            foreach ($node['properties'] as $field => $child) {
                self::assertFieldKey($eventKey, $field, $path);
                self::assertNode($eventKey, $child, $path.'.'.$field);
            }
        }

        if (array_key_exists('items', $node)) {
            if ($type !== 'array') {
                throw DescriptorValidationException::eventPayload(
                    $eventKey,
                    '[items] may be declared only on an array field.',
                    $path,
                );
            }

            self::assertNode($eventKey, $node['items'], $path.'[]');
        }
    }

    /** @param array<string, mixed> $node */
    private static function assertEnum(string $eventKey, array $node, string $type, string $path): void
    {
        $values = $node['enum'];

        if (! is_array($values) || ! array_is_list($values) || $values === []) {
            throw DescriptorValidationException::eventPayload(
                $eventKey,
                '[enum] must be a non-empty list.',
                $path,
            );
        }

        foreach ($values as $value) {
            if (! self::matchesType($type, $value)) {
                throw DescriptorValidationException::eventPayload(
                    $eventKey,
                    '[enum] values must match the declared type.',
                    $path,
                );
            }
        }

        if (count(array_unique($values, SORT_REGULAR)) !== count($values)) {
            throw DescriptorValidationException::eventPayload(
                $eventKey,
                '[enum] must not contain duplicate values.',
                $path,
            );
        }
    }

    private static function assertFieldKey(string $eventKey, mixed $field, ?string $parentPath): void
    {
        if (is_string($field) && trim($field) !== '' && $field === trim($field)) {
            return;
        }

        throw DescriptorValidationException::eventPayload(
            $eventKey,
            'contains an invalid field key.',
            $parentPath,
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $value
     */
    private static function assertObjectValue(
        string $eventKey,
        array $schema,
        array $value,
        ?string $parentPath,
    ): void {
        foreach ($schema as $field => $node) {
            $path = $parentPath === null ? $field : $parentPath.'.'.$field;

            if (! array_key_exists($field, $value)) {
                if (($node['required'] ?? true) === true) {
                    throw DescriptorValidationException::eventPayload($eventKey, 'is required.', $path);
                }

                continue;
            }

            self::assertValue($eventKey, $node, $value[$field], $path);
        }

        foreach ($value as $field => $_) {
            if (! is_string($field) || ! array_key_exists($field, $schema)) {
                $path = $parentPath === null ? (string) $field : $parentPath.'.'.$field;
                throw DescriptorValidationException::eventPayload($eventKey, 'is not declared.', $path);
            }
        }
    }

    /** @param array<string, mixed> $node */
    private static function assertValue(string $eventKey, array $node, mixed $value, string $path): void
    {
        if ($value === null) {
            if (($node['nullable'] ?? false) === true) {
                return;
            }

            throw DescriptorValidationException::eventPayload($eventKey, 'must not be null.', $path);
        }

        $type = $node['type'];

        if (! self::matchesType($type, $value)) {
            throw DescriptorValidationException::eventPayload(
                $eventKey,
                "must match declared type [{$type}].",
                $path,
            );
        }

        if (isset($node['enum']) && ! in_array($value, $node['enum'], true)) {
            throw DescriptorValidationException::eventPayload($eventKey, 'must be one of the declared enum values.', $path);
        }

        if (isset($node['format']) && ! self::matchesFormat($node['format'], $value)) {
            throw DescriptorValidationException::eventPayload(
                $eventKey,
                "must match declared format [{$node['format']}].",
                $path,
            );
        }

        if ($type === 'object' && array_key_exists('properties', $node)) {
            /** @var array<string, mixed> $value */
            self::assertObjectValue($eventKey, $node['properties'], $value, $path);
        }

        if ($type === 'array' && isset($node['items'])) {
            foreach ($value as $index => $item) {
                self::assertValue($eventKey, $node['items'], $item, $path.'.'.$index);
            }
        }
    }

    private static function matchesType(string $type, mixed $value): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || (is_float($value) && is_finite($value)),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
            default => false,
        };
    }

    private static function matchesFormat(string $format, mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return match ($format) {
            'uuid' => preg_match(
                '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/iD',
                $value,
            ) === 1,
            'date' => self::isDate($value),
            'date-time' => self::isDateTime($value),
            default => false,
        };
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function isDateTime(string $value): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})\z/D', $value) !== 1) {
            return false;
        }

        try {
            $parsed = date_parse($value);

            return $parsed['warning_count'] === 0 && $parsed['error_count'] === 0;
        } catch (Throwable) {
            return false;
        }
    }
}
