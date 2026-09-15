<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

/** Strategy contract for one allowlisted URL filter. */
interface Filter
{
    public function apply(Builder $query, mixed $value): void;
}
