<?php

declare(strict_types=1);

namespace Nexia\Support;

/** App-neutral SHA-256 fingerprint for nested payloads with stable object-key ordering. */
final class CanonicalPayloadFingerprint
{
    public static function sha256(mixed $payload, int $jsonFlags = 0): string
    {
        return hash('sha256', self::json($payload, $jsonFlags));
    }

    public static function json(mixed $payload, int $jsonFlags = 0): string
    {
        return json_encode(
            self::canonicalize($payload),
            JSON_THROW_ON_ERROR | $jsonFlags,
        );
    }

    public static function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(static fn (mixed $item): mixed => self::canonicalize($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize($item);
        }

        return $value;
    }
}
