<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use JsonException;

/** Shared, fail-closed v1 bounds for document-data descriptor and result payloads. */
final class SignatureDocumentDataLimits
{
    public const MAX_SOURCE_BINDINGS = 20;

    public const MAX_TOP_LEVEL_FIELDS = 50;

    public const MAX_MAPPED_VALUES = 200;

    public const MAX_RESOLVED_ITEM_FIELDS = 50;

    public const MAX_NESTING_DEPTH = 3;

    public const MAX_STRING_CHARACTERS = 4096;

    public const MAX_TEXT_CHARACTERS = 16384;

    public const MAX_LIST_ITEMS = 50;

    public const MAX_SOURCE_RESULT_BYTES = 131072;

    public const MAX_SNAPSHOT_BYTES = 524288;

    private function __construct() {}

    public static function assertBoundedJsonValue(mixed $value, string $context, int $depth = 0): void
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException("{$context} must not contain a non-finite number.");
            }

            return;
        }

        if (is_string($value)) {
            self::assertStringLength($value, self::MAX_TEXT_CHARACTERS, $context);

            return;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("{$context} must contain JSON-safe values only.");
        }

        if ($depth >= self::MAX_NESTING_DEPTH) {
            throw new InvalidArgumentException("{$context} exceeds the maximum JSON nesting depth.");
        }

        if (count($value) > self::MAX_LIST_ITEMS) {
            throw new InvalidArgumentException("{$context} exceeds the maximum item count.");
        }

        if (! array_is_list($value)) {
            foreach (array_keys($value) as $key) {
                if (! is_string($key) || $key === '' || $key !== trim($key)) {
                    throw new InvalidArgumentException("{$context} object keys must be normalized non-blank strings.");
                }
            }
        }

        foreach ($value as $child) {
            self::assertBoundedJsonValue($child, $context, $depth + 1);
        }
    }

    public static function assertPayloadByteLength(mixed $value, int $maximum, string $context): void
    {
        try {
            $encoded = json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException("{$context} must be JSON serializable.", previous: $exception);
        }

        if (strlen($encoded) > $maximum) {
            throw new InvalidArgumentException("{$context} exceeds the maximum serialized payload size.");
        }
    }

    public static function assertStringLength(string $value, int $maximum, string $context): void
    {
        if (mb_strlen($value) > $maximum) {
            throw new InvalidArgumentException("{$context} exceeds the maximum string length.");
        }
    }
}
