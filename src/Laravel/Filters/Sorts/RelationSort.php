<?php

declare(strict_types=1);

namespace Nexia\Laravel\Filters\Sorts;

use Nexia\Laravel\Filters\Contracts\Sort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Sort by a column on the first related row.
 *
 * Generic enough to cover most "sort users by their org name" /
 * "sort orders by their customer name" cases without a dedicated
 * Sort class. Auto-derives the join shape from the relation
 * instance (HasOne, HasMany, BelongsToMany).
 *
 * Usage:
 *   'organization' => new RelationSort(
 *       relation: 'organizations',
 *       column: 'name',
 *       primaryColumn: 'is_primary',   // pivot flag — prefer the primary first
 *   ),
 *
 *   'role' => new RelationSort('roles', 'name'),
 *
 *   'customer' => new RelationSort('customer', 'company_name'),
 *
 * Users / parents without any related row land at the end on `asc`
 * (NULL last) and the start on `desc`, per database default NULL
 * ordering.
 */
class RelationSort implements Sort
{
    /**
     * @param  string  $relation  Relation method name on the model
     * @param  string  $column  Column on the related table
     * @param  string|null  $primaryColumn  Pivot column flagging the
     *                                      "primary" row to prefer first (e.g. `is_primary` on the
     *                                      `organization_user` pivot). Only honored for BelongsToMany.
     */
    public function __construct(
        private readonly string $relation,
        private readonly string $column,
        private readonly ?string $primaryColumn = null,
    ) {}

    public function apply(Builder $query, string $direction): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $model = $query->getModel();
        $relation = $model->{$this->relation}();
        if (! $relation instanceof Relation) {
            throw new InvalidArgumentException(
                "RelationSort: `{$this->relation}` did not return an "
                .'Eloquent relation instance.',
            );
        }
        $related = $relation->getRelated();
        $relatedTable = $related->getTable();
        $parentTable = $model->getTable();
        $parentKey = $model->getKeyName();

        $sub = DB::table($relatedTable);

        if ($relation instanceof BelongsToMany) {
            $pivotTable = $relation->getTable();
            $relatedPivotKey = $relation->getRelatedPivotKeyName();
            $foreignPivotKey = $relation->getForeignPivotKeyName();
            $relatedKeyName = $related->getKeyName();

            $sub->join(
                $pivotTable,
                "{$pivotTable}.{$relatedPivotKey}",
                '=',
                "{$relatedTable}.{$relatedKeyName}",
            )->whereColumn(
                "{$pivotTable}.{$foreignPivotKey}",
                "{$parentTable}.{$parentKey}",
            );

            if ($this->primaryColumn !== null) {
                $sub->orderByDesc("{$pivotTable}.{$this->primaryColumn}");
            }
        } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
            $foreignKey = $relation->getForeignKeyName();
            $localKey = $relation->getLocalKeyName();
            $sub->whereColumn(
                "{$relatedTable}.{$foreignKey}",
                "{$parentTable}.{$localKey}",
            );
        } else {
            // Fallback: rely on Eloquent's `getRelationExistenceQuery`
            // to constrain the subquery. Covers BelongsTo, MorphMany,
            // and any custom relation that follows the convention.
            $existence = $relation->getRelationExistenceQuery(
                $related->newQuery(),
                $query,
                ['*'],
            )->toBase();
            $sub->mergeWheres(
                $existence->wheres ?? [],
                $existence->bindings['where'] ?? [],
            );
        }

        $sub->orderBy("{$relatedTable}.{$this->column}")
            ->limit(1)
            ->select("{$relatedTable}.{$this->column}");

        $query->orderBy($sub, $direction);
    }
}
