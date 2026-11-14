<?php

declare(strict_types=1);

namespace Nexia\Identity\Query;

use Exception;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\PartyBusinessDirectory;
use Nexia\Identity\Contracts\PartyRelationshipDirectory;
use Nexia\Identity\IdentityQueries;

/** Actor-scoped Party business and relationship reads. */
final class IdentityBusinessQuery implements IdentityRead
{
    private array $conditions = [];
    private ?string $search = null;
    private ?string $selection = null;
    private ?array $page = null;
    private bool $invalid = false;

    public function __construct(
        private readonly ?Actor $actor,
        private readonly PartyBusinessDirectory|IdentityQueries|null $business,
        private readonly PartyRelationshipDirectory|IdentityQueries|null $relationships,
    ) {
        if (func_num_args() > 3) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
    }

    public function where(string $field, mixed $value): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        if (array_key_exists($field, $copy->conditions) || is_array($value)) {
            $copy->invalid = true;
        }
        $copy->conditions[$field] = ['many' => false, 'value' => $value];

        return $copy;
    }

    public function whereIn(string $field, array $values): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        if (array_key_exists($field, $copy->conditions) || ! array_is_list($values)) {
            $copy->invalid = true;
        }
        $copy->conditions[$field] = ['many' => true, 'value' => $values];

        return $copy;
    }

    public function search(string $text): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->search = $text;

        return $copy;
    }

    public function select(string $selection): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->selection = $selection;

        return $copy;
    }

    public function page(int $number, int $perPage): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->page = [$number, $perPage];

        return $copy;
    }

    public function get(): array
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        try {
            if ($this->invalid || $this->actor === null) {
                throw new InvalidArgumentException;
            }
            if ($this->selection === 'relationship') {
                if ($this->search !== null || $this->page !== null || count($this->conditions) !== 1
                    || ! isset($this->conditions['public_id']) || $this->conditions['public_id']['many']) {
                    throw new UnsupportedIdentityQuery;
                }
                $relationships = $this->relationships instanceof IdentityQueries
                    ? $this->relationships->resolveDirectory('relationship') : $this->relationships;
                if (! $relationships instanceof PartyRelationshipDirectory) {
                    throw new UnsupportedIdentityQuery;
                }
                $id = $this->values('public_id')[0];
                $item = $relationships->findVisibleByPublicId($this->actor, $id);

                return $this->success($item === null ? [] : [$item]);
            }
            $business = $this->business instanceof IdentityQueries ? $this->business->resolveDirectory('business') : $this->business;
            if (! $business instanceof PartyBusinessDirectory) {
                throw new UnsupportedIdentityQuery;
            }
            if (in_array($this->selection, ['roles', 'relationships'], true)) {
                if ($this->search !== null || $this->page !== null || count($this->conditions) !== 1 || ! isset($this->conditions['public_id'])) {
                    throw new UnsupportedIdentityQuery;
                }
                $ids = $this->values('public_id');
                $groups = $this->selection === 'roles'
                    ? $business->activeRoles($this->actor, $ids)
                    : $business->activeRelationships($this->actor, $ids);

                return $this->success(array_map(static fn ($key, $value): array => ['key' => $key, 'value' => $value], array_keys($groups), $groups));
            }
            if ($this->selection !== null || $this->search === null
                || array_diff(array_keys($this->conditions), ['role', 'type']) !== []) {
                throw new UnsupportedIdentityQuery;
            }
            [$number, $perPage] = $this->page ?? [1, 25];
            $roles = isset($this->conditions['role']) ? $this->values('role') : [];
            $types = isset($this->conditions['type']) ? $this->values('type') : [];
            $result = $business->search($this->actor, $this->search, $roles, $types, $number, $perPage);
            if (! isset($result['items'], $result['has_more']) || ! is_array($result['items'])
                || ! array_is_list($result['items']) || ! is_bool($result['has_more'])) {
                throw new \RuntimeException;
            }

            return $this->success($result['items'], [
                'number' => $number, 'last' => null, 'perPage' => $perPage,
                'total' => null, 'hasMore' => $result['has_more'],
            ]);
        } catch (UnsupportedIdentityQuery) {
            return IdentityQuery::error('UNSUPPORTED_IMPLEMENTATION');
        } catch (Exception $error) {
            return IdentityQuery::failure($error);
        }
    }

    private function values(string $field): array
    {
        $condition = $this->conditions[$field];
        $values = $condition['many'] ? $condition['value'] : [$condition['value']];
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException;
            }
        }

        return $values;
    }

    private function success(array $data, ?array $page = null): array
    {
        return ['status' => 'success', 'data' => $data, 'page' => $page, 'error' => null];
    }
}
