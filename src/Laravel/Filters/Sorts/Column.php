<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Nexia\Laravel\Filters\Contracts\Sort;

/**
 * Plain `orderBy(column, direction)` sort. The trivial case.
 *
 * `ResourceListFields::field(sortable: true)` creates this sort for the
 * declared key. Pass an explicit Column when a public sort key maps to a
 * different database column.
 */
class Column implements Sort
{
    public function __construct(
        private readonly string $column,
    ) {}

    public function apply(Builder $query, string $direction): void
    {
        $query->orderBy($this->column, $direction);
    }
}
