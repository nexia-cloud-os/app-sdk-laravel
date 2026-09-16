<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;

/**
 * Actor and organization context restored by Core for owner-authorized reference resolution.
 *
 * @param  array<string, string|list<string>>  $filters  Owner-defined selector filters.
 * @param  string|null  $sort  Owner-defined selector sort, prefixed with `-` for descending order.
 */
final readonly class ResourceReferenceResolutionContext
{
    public function __construct(
        public ?LegalEntity $legalEntity,
        public Actor $actor,
        public DateTimeImmutable $asOf,
        public string $purpose,
        public ?OperatingUnit $operatingUnit = null,
        public bool $includeProtectedValues = false,
        public ?DateTimeImmutable $effectiveThrough = null,
        public array $filters = [],
        public ?string $sort = null,
    ) {
        if (trim($purpose) === '' || mb_strlen($purpose) > 160) {
            throw new InvalidArgumentException('Resource Reference resolution requires a bounded purpose.');
        }

        if ($effectiveThrough !== null && $effectiveThrough < $asOf) {
            throw new InvalidArgumentException('Resource Reference effective-through must not precede as-of.');
        }

        foreach ($filters as $key => $values) {
            $values = is_array($values) ? $values : [$values];
            if (! is_string($key)
                || trim($key) === ''
                || mb_strlen($key) > 80
                || count($values) > 50
                || array_filter(
                    $values,
                    static fn (mixed $value): bool => ! is_string($value) || mb_strlen($value) > 160,
                ) !== []) {
                throw new InvalidArgumentException('Resource Reference filters must contain bounded string keys and values.');
            }
        }

        if ($sort !== null
            && (mb_strlen($sort) > 80 || preg_match('/^-?[A-Za-z][A-Za-z0-9_.-]*$/D', $sort) !== 1)) {
            throw new InvalidArgumentException('Resource Reference sort must be a bounded field name.');
        }
    }
}
