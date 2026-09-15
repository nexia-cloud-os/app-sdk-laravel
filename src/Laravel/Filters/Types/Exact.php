<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Types;

use Illuminate\Database\Eloquent\Builder;
use Nexia\Laravel\Filters\Contracts\Filter;

/** Exact equality filter with multi-select support. */
final class Exact implements Filter
{
    public function __construct(
        private readonly string $column,
    ) {}

    public function apply(Builder $query, mixed $value): void
    {
        if (is_array($value)) {
            $values = array_values(array_filter(
                $value,
                static fn (mixed $candidate): bool => $candidate !== '' && $candidate !== null,
            ));

            if ($values !== []) {
                $query->whereIn($this->column, $values);
            }

            return;
        }

        if ($value === '' || $value === null) {
            return;
        }

        $query->where($this->column, '=', $value);
    }
}
