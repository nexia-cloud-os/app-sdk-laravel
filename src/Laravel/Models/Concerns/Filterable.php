<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Nexia\Laravel\Filters\Contracts\Filter;
use Nexia\Laravel\Filters\Contracts\Sort;
use Nexia\Laravel\Resources\ResourceListFields;
use Nexia\Laravel\Resources\ResourceListFieldsAugmenterRegistry;
use Nexia\Resources\ResourceListQuery;
use Nexia\Resources\ResourceListQuerySchema;

/**
 * Declarative filter / sort / search support for tenant collection
 * resources. Models declare every query capability once through
 * `resourceListFields()`; the same catalog drives request validation,
 * query application, Scout projection, and the frontend list schema.
 *
 * Models opt in:
 *
 *   class User extends Authenticatable
 *   {
 *       use Filterable;
 *
 *       public function resourceListFields(): ResourceListFields
 *       {
 *           return ResourceListFields::make()
 *               ->field('name', searchable: true, sortable: true)
 *               ->field('email', searchable: true, sortable: true)
 *               ->field('created_at', sortable: true)
 *               ->field(
 *                   'operating_unit',
 *                   filter: new HasRelation('operatingUnits', 'id'),
 *                   multiple: true,
 *                   label: 'users.operating_units.label',
 *                   options: fn (): array => [...],
 *               );
 *       }
 *   }
 *
 * Controllers stay one-liner:
 *
 *   User::filters()
 *       ->defaultSort('id', 'desc')
 *       ->paginate($perPage);
 *
 * URL contract mirrors the frontend's `useResourceListParams`:
 *
 *   ?search=text                 (Scout-compatible search across searchable fields)
 *   ?sort=key|-key               (sortable-field allowlist)
 *   ?filter[key]=value           (filterable-field allowlist)
 *   ?filter[key][]=a&filter[key][]=b   (array → multi-value strategy)
 *
 * Anything outside the model's declared catalogs is silently ignored
 * (F-8 spirit: arbitrary URL params cannot reach the database).
 *
 * @method static Builder filters(ResourceListQuery $query)
 * @method static Builder defaultSort(string $column, string $direction = 'asc')
 */
trait Filterable
{
    /**
     * Track whether a user-supplied `?sort=` was applied during the
     * `filters()` scope. `defaultSort()` consults this and only fires
     * if no URL sort took effect.
     */
    private bool $filterableSortApplied = false;

    public function resourceListFields(): ResourceListFields
    {
        return ResourceListFields::make();
    }

    /**
     * Self-describing list schema for the frontend. Combines the
     * sortable / filterable / searchable catalogs into one payload
     * so controllers can ship it as `meta.list_schema` and the
     * frontend can drop its mirrored static declarations.
     *
     * @return array{
     *   sortable: list<string>,
     *   filterable: list<array{key: string, multiple: bool, label: string, options: list<array{value: string, label: string}>}>,
     *   searchable: list<string>,
     *   resource_key?: string
     * }
     */
    public function listSchema(): array
    {
        return $this->effectiveResourceListFields()->schema();
    }

    public function resourceListQuerySchema(
        int $defaultPerPage = 25,
        int $minimumPerPage = 1,
        int $maximumPerPage = 100,
    ): ResourceListQuerySchema {
        $fields = $this->effectiveResourceListFields();

        return new ResourceListQuerySchema(
            filterKeys: array_keys($fields->filterCatalog()),
            sortKeys: array_keys($fields->sortCatalog()),
            defaultPerPage: $defaultPerPage,
            minimumPerPage: $minimumPerPage,
            maximumPerPage: $maximumPerPage,
        );
    }

    /**
     * Resolve the model catalog with the host's app-neutral platform fields.
     * The same effective catalog drives list metadata, request validation, and
     * query application so an injected field cannot drift across those paths.
     */
    private function effectiveResourceListFields(): ResourceListFields
    {
        $fields = clone $this->resourceListFields();
        ResourceListFieldsAugmenterRegistry::augment($fields, $this);

        return $fields;
    }

    /**
     * Apply the model's filters, search, and sorts in one scope.
     */
    public function scopeFilters(
        Builder $query,
        ResourceListQuery $resourceListQuery,
    ): Builder {
        $model = $query->getModel();
        $fields = $model->effectiveResourceListFields();

        $this->applyFilterableSearch($query, $resourceListQuery->search, $fields->searchableColumns());
        $this->applyFilterableFilters($query, $resourceListQuery->filters, $fields->filterCatalog());
        $this->applyFilterableSort($query, $resourceListQuery, $fields->sortCatalog());

        return $query;
    }

    /**
     * Apply a fallback `orderBy` when the request supplied no valid
     * `?sort=` (or `filters()` was not called for this query).
     *
     * @param  'asc'|'desc'  $direction
     */
    public function scopeDefaultSort(
        Builder $query,
        string $column,
        string $direction = 'asc',
    ): Builder {
        $model = $query->getModel();
        if ($model->wasFilterableSortApplied()) {
            return $query;
        }
        $query->orderBy(
            $column,
            $direction === 'desc' ? 'desc' : 'asc',
        );

        return $query;
    }

    public function wasFilterableSortApplied(): bool
    {
        return $this->filterableSortApplied;
    }

    public function markFilterableSortApplied(): void
    {
        $this->filterableSortApplied = true;
    }

    /**
     * Resolve `?search=` with Laravel Scout's engine semantics. The database
     * engine applies its ordinary substring predicate directly to the current
     * Eloquent query; engines with their own index return candidate keys that
     * are intersected with it. Permission, tenant / structural-scope
     * visibility, explicit filters, sort, and pagination always stay on this
     * query.
     *
     * @param  list<string>  $columns  the model's searchable field keys
     */
    private function applyFilterableSearch(
        Builder $query,
        ?string $term,
        array $columns,
    ): void {
        if ($columns === [] || $term === null) {
            return;
        }

        $model = $query->getModel();
        // Defence in depth: a model with searchable columns must be
        // Scout-searchable (enforced by the contract test). Skip rather
        // than fatal if that invariant is ever violated.
        if (! method_exists($model, 'search')) {
            return;
        }

        // Scout's database engine otherwise hydrates every matching model in
        // order to return its keys, only for this scoped query to issue a
        // second `WHERE IN (...)` query. The shared Scout concern can apply
        // the same database predicate directly to this query, preserving the
        // caller's visibility, filters, sort, and paginator while avoiding an
        // unbounded intermediate model/key collection.
        if (method_exists($model, 'applyFilterableDatabaseSearch')
            && $model->applyFilterableDatabaseSearch($query, $term, $columns)) {
            return;
        }

        $ids = $model::search($term)->keys();
        if ($ids->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereKey($ids);
    }

    /**
     * @param  array<string, Filter>  $catalog
     */
    private function applyFilterableFilters(
        Builder $query,
        array $values,
        array $catalog,
    ): void {
        foreach ($catalog as $key => $filter) {
            if (! $filter instanceof Filter) {
                throw new InvalidArgumentException(
                    "Filterable entry [{$key}] must implement "
                    .Filter::class.'.',
                );
            }
            if (! array_key_exists($key, $values)) {
                continue;
            }
            $filter->apply($query, $values[$key]);
        }
    }

    /**
     * @param  array<string, Sort>  $catalog
     */
    private function applyFilterableSort(
        Builder $query,
        ResourceListQuery $resourceListQuery,
        array $catalog,
    ): void {
        $sort = $resourceListQuery->sort;
        if ($sort === null) {
            return;
        }

        $catalog[$sort->key]->apply($query, $sort->direction);
        $query->getModel()->markFilterableSortApplied();
    }
}
