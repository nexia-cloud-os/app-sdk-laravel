<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Strategy contract for a single URL sort applicator.
 *
 * Layouts register Sort instances against URL sort keys
 * (`?sort=key|-key`). The `Filterable` model trait invokes
 * `apply()` with the parsed direction when the key matches.
 */
interface Sort
{
    /**
     * Apply this sort to the query.
     *
     * @param  'asc'|'desc'  $direction
     */
    public function apply(Builder $query, string $direction): void;
}
