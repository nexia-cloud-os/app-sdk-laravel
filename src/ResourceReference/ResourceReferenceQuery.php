<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;

/** Immutable selector for a single owner-authorized Resource Reference read. */
final class ResourceReferenceQuery
{
    private array $options = [];
    private array $filters = [];
    private ?string $sort = null;
    private ?DateTimeImmutable $asOf = null;
    private ?DateTimeImmutable $through = null;
    private bool $hasThrough = false;
    private bool $legacyContextFilters = false;

    /** @internal */
    public function __construct(
        private readonly object $owner,
        private readonly string $resourceKey,
        private readonly ResourceReferenceResolutionContext $context,
        private readonly Closure $run,
    ) {
        if (func_num_args() > 4) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }
    }

    public function where(string $field, mixed $value): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->whereInValue($field, $value, false);
    }

    public function whereIn(string $field, array $values): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->whereInValue($field, $values, true);
    }

    private function whereInValue(string $field, mixed $value, bool $multiple): self
    {
        $next = clone $this;
        if ($field === 'id') {
            $key = $multiple ? 'ids' : 'id';
            if (array_key_exists($key, $next->options)) {
                $next->options['_invalid'] = true;
            }
            $next->options[$key] = $value;

            return $next;
        }
        if (array_key_exists($field, $next->filters) || array_key_exists($field, $this->context->filters)) {
            $next->options['_invalid'] = true;
        }
        $next->filters[$field] = $value;

        return $next;
    }

    public function search(string $query): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->option('search', $query);
    }

    public function searches(array $queries): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->option('queries', $queries);
    }

    public function complete(): self
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->option('complete', true);
    }

    public function limit(int $limit): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->option('limit', $limit);
    }

    public function page(int $page, int $perPage = 25): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->options(['page' => $page, 'per_page' => $perPage]);
    }

    public function effectiveRange(DateTimeImmutable $from, DateTimeImmutable $until): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return $this->options(['from' => $from, 'until' => $until]);
    }

    public function asOf(DateTimeImmutable $asOf): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $next = clone $this;
        if ($next->asOf !== null) {
            $next->options['_invalid'] = true;
        }
        $next->asOf = $asOf;

        return $next;
    }

    public function effectiveThrough(?DateTimeImmutable $through): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $next = clone $this;
        if ($next->hasThrough) {
            $next->options['_invalid'] = true;
        }
        $next->through = $through;
        $next->hasThrough = true;

        return $next;
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $next = clone $this;
        if ($next->sort !== null || $this->context->sort !== null || ! in_array($direction, ['asc', 'desc'], true)) {
            $next->options['_invalid'] = true;
        }
        $next->sort = ($direction === 'desc' ? '-' : '').$field;

        return $next;
    }

    /** The provider may ignore legacy context filters; this never authorizes a read. */
    public function allowLegacyContextFilters(): self
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $next = clone $this;
        $next->legacyContextFilters = true;

        return $next;
    }

    /** @internal Host adapters use the same selector validation as fluent methods. */
    public function options(array $options): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $next = clone $this;
        if (array_intersect_key($next->options, $options) !== []) {
            $next->options['_invalid'] = true;
        }
        $next->options = array_merge($next->options, $options);

        return $next;
    }

    private function option(string $key, mixed $value): self
    {
        return $this->options([$key => $value]);
    }

    /** @return array{status:string,data:array,page:?array,error:?array} */
    public function get(): array
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $request = $this->snapshot($this->owner);

        return ($this->run)($request['resource_key'], $request['context'], $request['options']);
    }

    /** @internal */
    public function snapshot(object $owner): array
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        if ($owner !== $this->owner) {
            return [];
        }
        $options = $this->options;
        if ($this->legacyContextFilters) {
            $options['_allow_legacy_context_filters'] = true;
        }
        $context = $this->context;
        try {
            $context = new ResourceReferenceResolutionContext(
                $context->legalEntity, $context->actor, $this->asOf ?? $context->asOf, $context->purpose,
                $context->operatingUnit, $context->includeProtectedValues,
                $this->hasThrough ? $this->through : $context->effectiveThrough,
                array_merge($context->filters, $this->filters), $this->sort ?? $context->sort,
            );
            if ($this->filters !== [] || $this->sort !== null) {
                $options['_query_schema'] = true;
                if (! array_key_exists('id', $options) && ! array_key_exists('ids', $options)
                    && ! array_key_exists('queries', $options) && ! array_key_exists('from', $options)
                    && ! array_key_exists('until', $options)) {
                    $options['search'] ??= '';
                    if (! isset($options['limit'])) {
                        $options['page'] ??= 1;
                        $options['per_page'] ??= 25;
                    }
                }
            }
        } catch (InvalidArgumentException) {
            $options['_invalid'] = true;
        }

        return ['resource_key' => $this->resourceKey, 'context' => $context, 'options' => $options];
    }
}
