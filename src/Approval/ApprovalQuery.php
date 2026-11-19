<?php

declare(strict_types=1);

namespace Nexia\Approval;

use Closure;
use DateTimeInterface;
use Nexia\Approval\Contracts\ApprovalQueries;
use Nexia\Organization\Contracts\LegalEntity;

/** A read query over the Core-owned public approval projection. */
final class ApprovalQuery
{
    private array $conditions = [];

    private array $orders = [];

    public function __construct(
        private readonly ApprovalQueries $host,
        private readonly LegalEntity $legalEntity,
    ) {}

    public function where(string|Closure $field, mixed $operator = null, mixed $value = null): self
    {
        return $this->comparison('and', $field, $operator, $value, func_num_args());
    }

    public function orWhere(string|Closure $field, mixed $operator = null, mixed $value = null): self
    {
        return $this->comparison('or', $field, $operator, $value, func_num_args());
    }

    public function whereIn(string $field, iterable $values, string $boolean = 'and', bool $not = false): self
    {
        return $this->condition($boolean, $not ? 'not_in' : 'in', $field, $this->values($values));
    }

    public function whereNotIn(string $field, iterable $values, string $boolean = 'and'): self
    {
        return $this->whereIn($field, $values, $boolean, true);
    }

    public function orWhereIn(string $field, iterable $values): self
    {
        return $this->whereIn($field, $values, 'or');
    }

    public function whereBetween(string $field, iterable $values, string $boolean = 'and', bool $not = false): self
    {
        return $this->condition($boolean, $not ? 'not_between' : 'between', $field, $this->values($values));
    }

    public function whereNotBetween(string $field, iterable $values, string $boolean = 'and'): self
    {
        return $this->whereBetween($field, $values, $boolean, true);
    }

    public function orWhereBetween(string $field, iterable $values): self
    {
        return $this->whereBetween($field, $values, 'or');
    }

    public function whereNull(string $field, string $boolean = 'and', bool $not = false): self
    {
        return $this->condition($boolean, $not ? 'not_null' : 'null', $field, null);
    }

    public function whereNotNull(string $field, string $boolean = 'and'): self
    {
        return $this->whereNull($field, $boolean, true);
    }

    public function throughResource(string $resourceKey, Closure $conditions): self
    {
        $resource = new self($this->host, $this->legalEntity);
        $conditions($resource);

        return $this->condition('and', 'through_resource', $resourceKey, $resource->conditions);
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orders[] = ['field' => $field, 'direction' => strtolower($direction)];

        return $this;
    }

    public function orderByDesc(string $field): self
    {
        return $this->orderBy($field, 'desc');
    }

    public function paginate(int $perPage = 25, int $page = 1): array
    {
        return $this->host->paginate($this->legalEntity, $this->conditions, $this->orders, $perPage, $page);
    }

    public function first(): ?array
    {
        return $this->host->first($this->legalEntity, $this->conditions, $this->orders);
    }

    public function find(string $publicId): ?array
    {
        $query = clone $this;
        if ($this->conditions !== []) {
            $query->conditions = [];
            $query->condition('and', 'group', null, $this->conditions);
        }

        return $query->where('public_id', $publicId)->first();
    }

    private function comparison(string $boolean, string|Closure $field, mixed $operator, mixed $value, int $arguments): self
    {
        if ($field instanceof Closure) {
            $nested = new self($this->host, $this->legalEntity);
            $field($nested);

            return $this->condition($boolean, 'group', null, $nested->conditions);
        }

        if ($arguments < 3) {
            $value = $operator;
            $operator = '=';
        }
        if ($value === null && in_array($operator, ['=', '!=', '<>'], true)) {
            return $this->whereNull($field, $boolean, $operator !== '=');
        }

        return $this->condition($boolean, (string) $operator, $field, $this->wireValue($value));
    }

    private function condition(string $boolean, string $operator, ?string $field, mixed $value): self
    {
        $this->conditions[] = compact('boolean', 'operator', 'field', 'value');

        return $this;
    }

    private function values(iterable $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $result[] = $this->wireValue($value);
        }

        return $result;
    }

    private function wireValue(mixed $value): mixed
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : $value;
    }
}
