<?php

declare(strict_types=1);

namespace Nexia\Resources;

use InvalidArgumentException;

/**
 * Validated list intent shared by HTTP adapters and persistence adapters.
 *
 * This kernel DTO deliberately has no request or Eloquent dependency. The
 * owning host converts transport input once, then passes this value through
 * its query adapter.
 */
final readonly class ResourceListQuery
{
    /** @param array<string, string|list<string>> $filters */
    public function __construct(
        public ?string $search,
        public ?ResourceListSort $sort,
        public array $filters,
        public int $page,
        public int $perPage,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromInput(array $input, ResourceListQuerySchema $schema): self
    {
        return new self(
            search: self::search($input['search'] ?? null),
            sort: self::sort($input['sort'] ?? null, $schema),
            filters: self::filters($input['filter'] ?? null, $schema),
            page: self::page($input['page'] ?? null),
            perPage: self::perPage($input['per_page'] ?? null, $schema),
        );
    }

    private static function search(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Resource list search must be a string.');
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function sort(mixed $value, ResourceListQuerySchema $schema): ?ResourceListSort
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Resource list sort must be a string.');
        }

        $direction = str_starts_with($value, '-') ? 'desc' : 'asc';
        $key = ltrim($value, '-');

        if ($key === '' || ! in_array($key, $schema->sortKeys, true)) {
            throw new InvalidArgumentException("Resource list sort [{$key}] is not allowed.");
        }

        return new ResourceListSort($key, $direction);
    }

    /** @return array<string, string|list<string>> */
    private static function filters(mixed $value, ResourceListQuerySchema $schema): array
    {
        if ($value === null || $value === []) {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Resource list filters must be an object.');
        }

        $filters = [];
        foreach ($value as $key => $rawValue) {
            if (! is_string($key) || ! in_array($key, $schema->filterKeys, true)) {
                $name = is_string($key) ? $key : get_debug_type($key);

                throw new InvalidArgumentException("Resource list filter [{$name}] is not allowed.");
            }

            $normalized = self::filterValue($rawValue, $key);
            if ($normalized !== null) {
                $filters[$key] = $normalized;
            }
        }

        return $filters;
    }

    /** @return string|list<string>|null */
    private static function filterValue(mixed $value, string $key): string|array|null
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("Resource list filter [{$key}] must be a string or string list.");
        }

        $values = [];
        foreach ($value as $candidate) {
            if (! is_string($candidate)) {
                throw new InvalidArgumentException("Resource list filter [{$key}] must contain only strings.");
            }

            $candidate = trim($candidate);
            if ($candidate !== '') {
                $values[] = $candidate;
            }
        }

        return $values === [] ? null : array_values(array_unique($values));
    }

    private static function page(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 1;
        }

        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            throw new InvalidArgumentException('Resource list page must be a positive integer.');
        }

        $page = (int) $value;
        if ($page < 1) {
            throw new InvalidArgumentException('Resource list page must be a positive integer.');
        }

        return $page;
    }

    private static function perPage(mixed $value, ResourceListQuerySchema $schema): int
    {
        if ($value === null || $value === '') {
            return $schema->defaultPerPage;
        }

        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            throw new InvalidArgumentException('Resource list page size must be a positive integer.');
        }

        $perPage = (int) $value;
        if ($perPage < $schema->minimumPerPage || $perPage > $schema->maximumPerPage) {
            throw new InvalidArgumentException(
                "Resource list page size must be between {$schema->minimumPerPage} and {$schema->maximumPerPage}.",
            );
        }

        return $perPage;
    }
}
