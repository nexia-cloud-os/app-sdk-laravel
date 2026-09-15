<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Sorts;

use Nexia\Laravel\Filters\Contracts\Sort;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Inline closure sort for derived columns: CASE expressions,
 * subqueries, JOIN-aware ordering. Prefer a dedicated Sort class
 * for non-trivial logic so the layout stays declarative.
 */
class Callback implements Sort
{
    public function __construct(
        private readonly Closure $applicator,
    ) {}

    public function apply(Builder $query, string $direction): void
    {
        ($this->applicator)($query, $direction);
    }
}
