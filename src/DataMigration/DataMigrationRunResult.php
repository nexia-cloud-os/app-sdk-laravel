<?php

declare(strict_types=1);

namespace Nexia\DataMigration;

use InvalidArgumentException;

/**
 * Small non-sensitive execution summary safe to expose through the host API.
 *
 * Raw rows, exception text, and nested source payloads deliberately do not fit
 * this contract. Apps may report counts, booleans, and stable public references.
 */
final readonly class DataMigrationRunResult
{
    /** @param array<string, bool|int|float|string|null> $metadata */
    public function __construct(public array $metadata = [])
    {
        if (count($metadata) > 50) {
            throw new InvalidArgumentException('Data migration result metadata is limited to 50 entries.');
        }

        foreach ($metadata as $key => $value) {
            if (preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $key) !== 1) {
                throw new InvalidArgumentException("Data migration result key [{$key}] is invalid.");
            }
            if (! is_bool($value) && ! is_int($value) && ! is_float($value)
                && ! is_string($value) && $value !== null) {
                throw new InvalidArgumentException("Data migration result [{$key}] must be scalar or null.");
            }
            if (is_string($value) && strlen($value) > 500) {
                throw new InvalidArgumentException("Data migration result [{$key}] is too long.");
            }
        }
    }
}
