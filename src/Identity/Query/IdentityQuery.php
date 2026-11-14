<?php

declare(strict_types=1);

namespace Nexia\Identity\Query;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Nexia\Identity\Contracts\ActorDirectory;
use Nexia\Identity\Contracts\BatchActorDirectory;
use Nexia\Identity\Contracts\BatchCanonicalPartyDirectory;
use Nexia\Identity\Contracts\BatchPartyDirectory;
use Nexia\Identity\Contracts\BatchPersonProfileDirectory;
use Nexia\Identity\Contracts\PersonProfileDirectory;
use Nexia\Identity\PersonDirectoryEntry;
use Nexia\Identity\IdentityQueries;
use Nexia\Laravel\Identity\Contracts\PartyDirectory;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Conditions are immutable so a query added to a batch cannot change later. */
final class IdentityQuery implements IdentityRead
{
    private array $conditions = [];
    private ?string $search = null;
    private ?int $limit = null;
    private array $excludedKeys = [];
    private bool $includeDeleted = false;
    private bool $canonical = false;
    private bool $required = false;
    private ?string $projection = null;
    private bool $invalid = false;

    public function __construct(
        private readonly string $kind,
        private PartyDirectory|ActorDirectory|PersonProfileDirectory|IdentityQueries|null $directory,
    ) {
        if (func_num_args() > 2) {
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

    /** @param list<mixed> $values */
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

    public function limit(int $limit): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->limit = $limit;

        return $copy;
    }

    /** @param list<int|string> $keys */
    public function excludeKeys(array $keys): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->excludedKeys = $keys;

        return $copy;
    }

    public function includeDeleted(bool $include = true): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->includeDeleted = $include;

        return $copy;
    }

    public function canonical(bool $canonical = true): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->canonical = $canonical;

        return $copy;
    }

    public function required(bool $required = true): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->required = $required;

        return $copy;
    }

    /** Select a scalar identity field or an existence result. */
    public function select(string $field): self
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $copy = clone $this;
        $copy->projection = $field;

        return $copy;
    }

    public function get(): array
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        try {
            if ($this->invalid) {
                throw new InvalidArgumentException;
            }
            if ($this->required && ! $this->supportsRequired()) {
                throw new UnsupportedIdentityQuery;
            }
            if ($this->directory instanceof IdentityQueries) {
                $resolved = clone $this;
                $resolved->directory = $this->directory->resolveDirectory($this->kind);

                return $resolved->get();
            }
            if ($this->projection !== null && ! in_array($this->projection, ['key', 'public_id', 'display_label', 'exists'], true)) {
                throw new UnsupportedIdentityQuery;
            }
            if ($this->kind === 'party' && ($this->conditions['email']['many'] ?? false)
                && ($this->required || $this->projection !== null)) {
                throw new UnsupportedIdentityQuery;
            }
            $optimized = $this->optimizedProjection();
            if ($optimized !== null) {
                return ['status' => 'success', 'data' => $optimized, 'page' => null, 'error' => null];
            }
            $data = match ($this->kind) {
                'party' => $this->parties(),
                'user' => $this->users(),
                'profile' => $this->profiles(),
                default => throw new UnsupportedIdentityQuery,
            };
            if ($this->required && $data === []) {
                return self::error('NOT_FOUND');
            }

            return ['status' => 'success', 'data' => $this->project($data), 'page' => null, 'error' => null];
        } catch (UnsupportedIdentityQuery) {
            return self::error('UNSUPPORTED_IMPLEMENTATION');
        } catch (Exception $error) {
            return self::failure($error);
        }
    }

    public static function error(string $code, ?int $status = null): array
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        return ['status' => 'error', 'data' => [], 'page' => null, 'error' => ['code' => $code, 'httpStatus' => $status]];
    }

    public static function failure(Exception $error): array
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        if ($error instanceof ValidationException) {
            return self::error('INVALID_QUERY', 422);
        }
        if ($error instanceof AuthenticationException) {
            return self::error('UNAUTHENTICATED', 401);
        }
        if ($error instanceof ModelNotFoundException) {
            return self::error('NOT_FOUND', 404);
        }
        if ($error instanceof AuthorizationException) {
            $status = $error->hasStatus() ? $error->status() : 403;
            $status = is_int($status) && $status >= 400 && $status <= 599 ? $status : 403;
            $code = match ($status) {
                400, 422 => 'INVALID_QUERY', 401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN',
                404 => 'NOT_FOUND', 408, 504 => 'TIMEOUT', 413 => 'TOO_LARGE', 429 => 'RATE_LIMITED',
                default => 'QUERY_FAILED',
            };
            return self::error($code, $status);
        }
        $properties = get_object_vars($error);
        $status = $error instanceof HttpExceptionInterface ? $error->getStatusCode() : ($properties['upstreamStatus'] ?? null);
        $status = is_int($status) && $status >= 400 && $status <= 599 ? $status : null;
        if (isset($properties['failureCode']) && is_string($properties['failureCode'])) {
            $runtimeStatus = match ($properties['failureCode']) {
                'RUNTIME_RESOURCE_READ_NOT_GRANTED' => 503,
                'RUNTIME_REFERENCE_DEADLINE_EXCEEDED' => 504,
                'RUNTIME_REFERENCE_CYCLE', 'RUNTIME_REFERENCE_DEPTH_EXCEEDED' => 508,
                default => null,
            };
            if ($runtimeStatus !== null && $status === null) {
                return self::error($properties['failureCode'], $runtimeStatus);
            }
        }
        $code = $status === null ? ($error instanceof InvalidArgumentException ? 'INVALID_ARGUMENT' : 'QUERY_FAILED') : match ($status) {
            400, 422 => 'INVALID_QUERY', 401 => 'UPSTREAM_AUTHENTICATION_FAILED', 403 => 'FORBIDDEN',
            404 => 'NOT_FOUND', 408, 504 => 'TIMEOUT', 413 => 'TOO_LARGE', 429 => 'RATE_LIMITED',
            default => 'QUERY_FAILED',
        };

        return self::error($code, $status === 401 ? 502 : $status);
    }

    private function optimizedProjection(): ?array
    {
        if ($this->projection === null || $this->canonical || $this->required || $this->search !== null
            || $this->limit !== null || $this->excludedKeys !== [] || count($this->conditions) !== 1) {
            return null;
        }
        $key = $this->conditions['key'] ?? null;
        $publicId = $this->conditions['public_id'] ?? null;
        if ($this->directory instanceof PartyDirectory && ! $this->includeDeleted) {
            if ($publicId !== null && ! $publicId['many'] && $this->projection === 'exists') {
                return [$this->directory->exists($this->stringValue($publicId))];
            }
            if ($key !== null && $this->projection === 'public_id') {
                $keys = $this->keys($key);
                if ($key['many']) {
                    $result = $this->directory->publicIdsByKeys($keys);
                    return array_map(static fn ($key, $value): array => ['key' => $key, 'value' => $value], array_keys($result), $result);
                }
                $value = $this->directory->publicIdByKey($keys[0]);
                return $value === null ? [] : [['key' => $keys[0], 'value' => $value]];
            }
        }
        if ($this->directory instanceof ActorDirectory) {
            if ($key !== null && $this->projection === 'exists' && ! $key['many']) {
                return [$this->directory->existsByKey($this->keys($key)[0], $this->includeDeleted)];
            }
            if ($this->includeDeleted) {
                return null;
            }
            if ($key !== null && $this->projection === 'public_id') {
                $keys = $this->keys($key);
                if ($key['many']) {
                    $result = $this->directory->publicIdsByKeys($keys);
                    return array_map(static fn ($key, $value): array => ['key' => $key, 'value' => $value], array_keys($result), $result);
                }
                $value = $this->directory->publicIdByKey($keys[0]);
                return $value === null ? [] : [['key' => $keys[0], 'value' => $value]];
            }
            if ($key !== null && ! $key['many'] && $this->projection === 'display_label') {
                $value = $this->directory->displayLabelByKey($this->keys($key)[0]);
                return $value === null ? [] : [['key' => $key['value'], 'value' => $value]];
            }
            if ($publicId !== null && ! $publicId['many'] && $this->projection === 'key') {
                $value = $this->directory->keyByPublicId($this->stringValue($publicId));
                return $value === null ? [] : [['key' => $value, 'value' => $value]];
            }
        }
        return null;
    }

    private function supportsRequired(): bool
    {
        if (! in_array($this->kind, ['party', 'user'], true) || $this->canonical
            || $this->search !== null || $this->limit !== null || $this->excludedKeys !== []
            || ($this->kind === 'party' && $this->includeDeleted) || count($this->conditions) !== 1) {
            return false;
        }
        $field = array_key_first($this->conditions);
        if (! in_array($field, ['key', 'public_id'], true)) {
            return false;
        }
        return ! $this->conditions[$field]['many'] && $this->conditions[$field]['value'] !== null;
    }

    /** @internal Reuse only an identical single-key lookup in the same scope. */
    public function batchCompatibleWith(self $other): bool
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        if ($this->directory instanceof IdentityQueries && ! $this->directory->sharesDirectory($this->kind)) {
            return false;
        }
        $field = $this->batchField();
        return $field !== null && $field === $other->batchField()
            && $this->kind === $other->kind && $this->directory === $other->directory
            && $this->includeDeleted === $other->includeDeleted && $this->required === $other->required
            && $this->conditions[$field]['value'] === $other->conditions[$field]['value'];
    }

    /** @internal @param list<self> $queries */
    public function batchGet(array $queries): array
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        $field = $this->batchField();
        if ($field === null || count($queries) < 2) {
            return array_map(static fn (self $query): array => $query->get(), $queries);
        }
        foreach ($queries as $query) {
            if (! $this->batchCompatibleWith($query)) {
                return array_map(static fn (self $query): array => $query->get(), $queries);
            }
        }
        return array_fill(0, count($queries), $this->get());
    }

    private function batchField(): ?string
    {
        if ($this->invalid || $this->canonical || $this->search !== null || $this->limit !== null
            || $this->excludedKeys !== [] || $this->projection !== null || count($this->conditions) !== 1) {
            return null;
        }
        $field = array_key_first($this->conditions);
        if (! in_array($field, $this->kind === 'profile' ? ['party_key'] : ($this->kind === 'party' ? ['key', 'public_id'] : ['key']), true)) {
            return null;
        }
        $condition = $this->conditions[$field];
        $value = $condition['value'];
        if ($condition['many'] || (! is_int($value) && (! is_string($value) || $value === ''))
            || ($field === 'public_id' && (! is_string($value) || mb_strtolower($value) !== $value))
            || ($field !== 'public_id' && is_string($value) && ctype_digit($value) && (string) (int) $value !== $value)) {
            return null;
        }
        return $field;
    }

    private function parties(): array
    {
        if (! $this->directory instanceof PartyDirectory || $this->includeDeleted) {
            throw new UnsupportedIdentityQuery;
        }
        $this->checkFields(['key', 'public_id', 'email', 'active', 'type']);
        $key = $this->conditions['key'] ?? null;
        $publicId = $this->conditions['public_id'] ?? null;
        $email = $this->conditions['email'] ?? null;
        $active = $this->scalar('active');
        $type = $this->scalar('type');
        if (($active !== null && $active !== true) || ($type !== null && $type !== 'person')) {
            throw new UnsupportedIdentityQuery;
        }
        if ($this->canonical) {
            if (count($this->conditions) !== 1 || $publicId === null || $this->search !== null || $this->limit !== null || $this->excludedKeys !== []) {
                throw new UnsupportedIdentityQuery;
            }
            $ids = $this->strings($publicId);
            if (! $publicId['many']) {
                $item = $this->directory->findCanonicalByPublicId($ids[0]);

                return $item === null ? [] : [$item];
            }
            $items = $this->directory instanceof BatchCanonicalPartyDirectory
                ? $this->directory->findCanonicalByPublicIds($ids)
                : array_map($this->directory->findCanonicalByPublicId(...), array_values(array_unique($ids)));

            return array_values(array_filter($items, static fn ($item): bool => $item !== null));
        }
        if ($key !== null || $publicId !== null) {
            if (count($this->conditions) !== 1 || $this->search !== null || $this->limit !== null || $this->excludedKeys !== []) {
                throw new UnsupportedIdentityQuery;
            }
            $condition = $key ?? $publicId;
            if ($key !== null) {
                $values = $this->keys($condition);
                $result = $condition['many'] ? $this->directory->findByKeys($values)
                    : [$this->required ? $this->directory->requireByKey($values[0]) : $this->directory->findByKey($values[0])];
            } else {
                $values = $this->strings($condition);
                $result = $condition['many'] ? array_values($this->directory->findByPublicIds($values))
                    : [$this->required ? $this->directory->requireByPublicId($values[0]) : $this->directory->findByPublicId($values[0])];
            }

            return array_values(array_filter($result, static fn ($item): bool => $item !== null));
        }
        if ($email !== null) {
            if ($this->search !== null || $this->excludedKeys !== [] || $this->canonical || ($active !== null && $active !== true) || ($type !== null && $type !== 'person')) {
                throw new UnsupportedIdentityQuery;
            }
            $limit = $this->limit ?? 2;
            $emails = $this->strings($email, true);
            if (! $email['many']) {
                return $this->directory->activePeopleByEmail($emails[0], $limit);
            }
            if ($this->directory instanceof BatchPartyDirectory) {
                $groups = $this->directory->activePeopleByEmails($emails, $limit);
            } else {
                $groups = [];
                foreach (array_unique(array_map(static fn (string $value): string => mb_strtolower(trim($value)), $emails)) as $value) {
                    if ($value !== '') {
                        $groups[$value] = $this->directory->activePeopleByEmail($value, $limit);
                    }
                }
            }

            return array_map(static fn ($key, $value): array => ['key' => $key, 'value' => $value], array_keys($groups), $groups);
        }
        if ($this->search !== null && $active === true) {
            $limit = $this->limit ?? 25;
            if ($type === 'person') {
                return $this->directory->searchActivePeople($this->search, $limit, $this->excludedKeys);
            }
            if ($this->excludedKeys !== []) {
                throw new UnsupportedIdentityQuery;
            }

            return $this->directory->activeSearch($this->search, $limit);
        }
        throw new UnsupportedIdentityQuery;
    }

    private function users(): array
    {
        if (! $this->directory instanceof ActorDirectory || $this->canonical || $this->search !== null || $this->limit !== null || $this->excludedKeys !== []) {
            throw new UnsupportedIdentityQuery;
        }
        $this->checkFields(['key', 'public_id', 'party_key', 'legal_entity_key', 'active']);
        $key = $this->conditions['key'] ?? null;
        $publicId = $this->conditions['public_id'] ?? null;
        $partyKey = $this->conditions['party_key'] ?? null;
        $legalEntity = $this->conditions['legal_entity_key'] ?? null;
        $active = $this->scalar('active');
        if ($legalEntity !== null && $active === true && ! $this->includeDeleted && ! $legalEntity['many']) {
            $entity = $this->keys($legalEntity)[0];
            if ($publicId === null && count($this->conditions) === 2) {
                return $this->directory->activeForLegalEntity($entity);
            }
            if ($publicId !== null && ! $publicId['many'] && count($this->conditions) === 3) {
                $actor = $this->directory->activeByPublicIdForLegalEntity($this->stringValue($publicId), $entity);

                return $actor === null ? [] : [$actor];
            }
        }
        if (count($this->conditions) !== 1) {
            throw new UnsupportedIdentityQuery;
        }
        if ($key !== null) {
            if (! $key['many'] && $key['value'] === null) {
                $actor = $this->directory->findByKey(null, $this->includeDeleted);
                return $actor === null ? [] : [$actor];
            }
            $values = $this->keys($key);
            $result = $key['many'] ? $this->directory->findByKeys($values, $this->includeDeleted)
                : [$this->required ? $this->directory->requireByKey($values[0], $this->includeDeleted) : $this->directory->findByKey($values[0], $this->includeDeleted)];
        } elseif ($publicId !== null && ! $this->includeDeleted) {
            $ids = $this->strings($publicId);
            $result = ! $publicId['many'] ? [$this->required ? $this->directory->requireByPublicId($ids[0]) : $this->directory->findByPublicId($ids[0])]
                : ($this->directory instanceof BatchActorDirectory
                    ? $this->directory->findByPublicIds($ids)
                    : array_map($this->directory->findByPublicId(...), array_values(array_unique($ids))));
        } elseif ($partyKey !== null && ! $partyKey['many'] && ! $this->includeDeleted) {
            $result = [$this->directory->findByPartyKey($this->keys($partyKey)[0])];
        } else {
            throw new UnsupportedIdentityQuery;
        }

        return array_values(array_filter($result, static fn ($item): bool => $item !== null));
    }

    private function profiles(): array
    {
        if (! $this->directory instanceof PersonProfileDirectory || $this->includeDeleted || $this->canonical
            || $this->search !== null || $this->limit !== null || $this->excludedKeys !== []
            || count($this->conditions) !== 1 || ! isset($this->conditions['party_key']) || $this->projection !== null) {
            throw new UnsupportedIdentityQuery;
        }
        $condition = $this->conditions['party_key'];
        if (! $condition['many']) {
            $keys = $this->keys($condition);
            $item = $this->directory->findByPartyKey($keys[0]);

            return $item === null ? [] : [['key' => $keys[0], 'value' => $item]];
        }
        if ($this->directory instanceof BatchPersonProfileDirectory) {
            $items = $this->directory->findByPartyKeys($condition['value']);
        } else {
            $keys = array_values(array_unique(array_filter($condition['value'],
                static fn (mixed $key): bool => is_int($key) || (is_string($key) && $key !== ''))));
            $items = [];
            foreach (array_unique($keys) as $key) {
                $item = $this->directory->findByPartyKey($key);
                if ($item !== null) {
                    $items[$key] = $item;
                }
            }
        }

        return array_map(static fn ($key, $value): array => ['key' => $key, 'value' => $value], array_keys($items), $items);
    }

    private function project(array $items): array
    {
        if ($this->projection === null) {
            return $items;
        }
        if ($this->projection === 'exists') {
            return [$items !== []];
        }
        if (! in_array($this->projection, ['key', 'public_id', 'display_label'], true)) {
            throw new UnsupportedIdentityQuery;
        }

        return array_map(function ($item): array {
            if ($item instanceof PersonDirectoryEntry) {
                $value = match ($this->projection) {
                    'key' => $item->key, 'public_id' => $item->publicId, 'display_label' => $item->displayLabel,
                };

                return ['key' => $item->key, 'value' => $value];
            }
            if (! is_object($item) || ! method_exists($item, 'key') || ! method_exists($item, 'publicId') || ! method_exists($item, 'displayLabel')) {
                throw new UnsupportedIdentityQuery;
            }
            $value = match ($this->projection) {
                'key' => $item->key(), 'public_id' => $item->publicId(), 'display_label' => $item->displayLabel(),
            };

            return ['key' => $item->key(), 'value' => $value];
        }, $items);
    }

    private function checkFields(array $allowed): void
    {
        if (array_diff(array_keys($this->conditions), $allowed) !== []) {
            throw new UnsupportedIdentityQuery;
        }
    }

    private function scalar(string $field): mixed
    {
        $condition = $this->conditions[$field] ?? null;
        if ($condition !== null && ($condition['many'] || $condition['value'] === null)) {
            throw new UnsupportedIdentityQuery;
        }

        return $condition['value'] ?? null;
    }

    private function keys(array $condition): array
    {
        $values = $condition['many'] ? $condition['value'] : [$condition['value']];
        foreach ($values as $value) {
            if ((! is_string($value) || $value === '') && ! is_int($value)) {
                throw new InvalidArgumentException;
            }
        }

        return $values;
    }

    private function strings(array $condition, bool $allowEmpty = true): array
    {
        $values = $condition['many'] ? $condition['value'] : [$condition['value']];
        foreach ($values as $value) {
            if (! is_string($value) || (! $allowEmpty && $value === '')) {
                throw new InvalidArgumentException;
            }
        }

        return $values;
    }

    private function stringValue(array $condition): string
    {
        return $this->strings($condition)[0];
    }
}
