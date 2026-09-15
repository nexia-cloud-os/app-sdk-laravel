<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Types;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Nexia\Laravel\Filters\Contracts\Filter;

/** Closure-backed filter for App-specific query predicates. */
final class Callback implements Filter
{
    public function __construct(
        private readonly Closure $applicator,
    ) {}

    public function apply(Builder $query, mixed $value): void
    {
        ($this->applicator)($query, $value);
    }
}
