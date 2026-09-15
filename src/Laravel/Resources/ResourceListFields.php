<?php

declare(strict_types=1);

namespace Nexia\Laravel\Resources;

use InvalidArgumentException;
use Nexia\Laravel\Filters\Contracts\Filter;
use Nexia\Laravel\Filters\Contracts\Sort;
use Nexia\Laravel\Filters\Sorts\Column;

/**
 * One declarative catalog for every backend capability of a resource-list
 * field. Presentation-only concerns such as table visibility stay in the
 * frontend column definition because a visual column need not map one-to-one
 * to a query field.
 */
final class ResourceListFields
{
    /**
     * @var array<string, array{
     *   searchable: bool,
     *   sort: Sort|null,
     *   filter: Filter|null,
     *   multiple: bool,
     *   label: string|null,
     *   options: list<array{value: string, label: string}>|callable(): list<array{value: string, label: string}>
     * }>
     */
    private array $fields = [];

    /** @var array<string, mixed> */
    private array $metadata = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * Declare a query field once and attach every capability it supports.
     * Re-declaring a key is rejected so the catalog cannot drift internally.
     *
     * @param  bool|Sort  $sortable  `true` sorts the declared key directly;
     *                               provide a Sort for aliases or derived values.
     * @param  list<array{value: string, label: string}>|callable(): list<array{value: string, label: string}>  $options
     */
    public function field(
        string $key,
        bool $searchable = false,
        bool|Sort $sortable = false,
        ?Filter $filter = null,
        bool $multiple = false,
        ?string $label = null,
        array|callable $options = [],
    ): self {
        if ($key === '') {
            throw new InvalidArgumentException('A resource-list field key cannot be empty.');
        }

        if (array_key_exists($key, $this->fields)) {
            throw new InvalidArgumentException("Resource-list field [{$key}] is already declared.");
        }

        if (! $searchable && $sortable === false && $filter === null) {
            throw new InvalidArgumentException(
                "Resource-list field [{$key}] must declare at least one query capability.",
            );
        }

        if ($filter === null && ($multiple || $label !== null || $options !== [])) {
            throw new InvalidArgumentException(
                "Resource-list field [{$key}] cannot declare filter metadata without a filter.",
            );
        }

        $sort = match (true) {
            $sortable instanceof Sort => $sortable,
            $sortable => new Column($key),
            default => null,
        };

        $this->fields[$key] = [
            'searchable' => $searchable,
            'sort' => $sort,
            'filter' => $filter,
            'multiple' => $multiple,
            'label' => $label,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Attach host or resource metadata to the self-describing list schema.
     * It does not affect the filter/sort/search allowlists.
     */
    public function metadata(string $key, mixed $value): self
    {
        if ($key === '') {
            throw new InvalidArgumentException('A resource-list metadata key cannot be empty.');
        }

        $this->metadata[$key] = $value;

        return $this;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->fields);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->fields);
    }

    /** @return list<string> */
    public function searchableColumns(): array
    {
        return array_keys(array_filter(
            $this->fields,
            static fn (array $field): bool => $field['searchable'],
        ));
    }

    /** @return array<string, Sort> */
    public function sortCatalog(): array
    {
        $sorts = [];
        foreach ($this->fields as $key => $field) {
            if ($field['sort'] instanceof Sort) {
                $sorts[$key] = $field['sort'];
            }
        }

        return $sorts;
    }

    /** @return array<string, Filter> */
    public function filterCatalog(): array
    {
        $filters = [];
        foreach ($this->fields as $key => $field) {
            if ($field['filter'] instanceof Filter) {
                $filters[$key] = $field['filter'];
            }
        }

        return $filters;
    }

    /**
     * @return array{
     *   sortable: list<string>,
     *   filterable: list<array{key: string, multiple: bool, label: string, options: list<array{value: string, label: string}>}>,
     *   searchable: list<string>,
     *   resource_key?: string
     * }
     */
    public function schema(): array
    {
        $filterable = [];
        foreach ($this->fields as $key => $field) {
            if (! $field['filter'] instanceof Filter) {
                continue;
            }

            $options = $field['options'];
            $filterable[] = [
                'key' => $key,
                'multiple' => $field['multiple'],
                'label' => $field['label'] ?? ucfirst($key),
                'options' => is_callable($options) ? $options() : $options,
            ];
        }

        return [
            ...$this->metadata,
            'sortable' => array_keys($this->sortCatalog()),
            'filterable' => $filterable,
            'searchable' => $this->searchableColumns(),
        ];
    }
}
