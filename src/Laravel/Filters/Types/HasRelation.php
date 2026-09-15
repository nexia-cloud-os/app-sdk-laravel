<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Types;

use Nexia\Laravel\Filters\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `whereHas` filter against a related table column.
 *
 * Usage:
 *   'organization' => new HasRelation('organizations', 'id'),
 *   'role' => new HasRelation('roles', 'name'),
 *
 * Multi-select friendly — array input becomes `whereIn` inside the
 * `whereHas` constraint.
 */
class HasRelation implements Filter
{
    public function __construct(
        private readonly string $relation,
        private readonly string $column,
    ) {}

    public function apply(Builder $query, mixed $value): void
    {
        $values = is_array($value)
            ? array_values(array_filter(
                $value,
                fn ($v) => $v !== '' && $v !== null,
            ))
            : ($value === '' || $value === null ? [] : [$value]);
        if ($values === []) {
            return;
        }
        $relation = $this->relation;
        $column = $this->column;
        $query->whereHas(
            $relation,
            fn (Builder $sub) => $sub->whereIn($column, $values),
        );
    }
}
