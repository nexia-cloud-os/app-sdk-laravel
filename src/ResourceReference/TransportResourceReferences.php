<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\ResourceReference\Contracts\BatchSearchResourceReferences;
use Nexia\ResourceReference\Contracts\CompleteEffectiveRangeReferences;
use Nexia\ResourceReference\Contracts\GroupedResourceReferences;
use Nexia\ResourceReference\Contracts\ResourceReferenceQueries;
use RuntimeException;
use Throwable;

/** Existing Resource Reference wire contract over a host-owned bounded transport. */
readonly class TransportResourceReferences implements GroupedResourceReferences, BatchSearchResourceReferences, CompleteEffectiveRangeReferences, ResourceReferenceQueries
{
    use ResourceReferenceQuerySupport;

    /** Legacy subclass overrides must explicitly opt in before standard dispatch bypasses them. */
    protected function supportsReferenceQueryOperation(string $method): bool
    {
        static $declared = [];
        $key = static::class.'::'.$method;

        return $declared[$key] ??= (new \ReflectionMethod($this, $method))->getDeclaringClass()->getName() === self::class;
    }

    /** The host may use the optional second callback to decode public snapshots once. */
    public function __construct(private Closure $send, private ?Closure $sendBatch = null) {}

    public function resolve(string $resourceKey, string $resourceId, ResourceReferenceResolutionContext $context): ?ResolvedResourceReference
    {
        $result = $this->request('resolve', $resourceKey, $context, ['resource_id' => $resourceId]);

        return $result === null ? null : ($context->includeProtectedValues
            ? $this->protectedReference($result, $resourceKey, $resourceId, $context) : $this->reference($result));
    }

    public function resolveMany(string $resourceKey, array $resourceIds, ResourceReferenceResolutionContext $context): array
    {
        foreach ($resourceIds as $id) {
            if (! is_string($id) || $id === '' || mb_strlen($id) > 255) {
                throw new InvalidArgumentException('Reference IDs must be non-empty strings of at most 255 characters.');
            }
        }
        $resourceIds = array_values(array_unique($resourceIds));
        if ($resourceIds === []) {
            return [];
        }

        $references = [];
        foreach (array_chunk($resourceIds, 100) as $ids) {
            foreach ($this->request('resolveMany', $resourceKey, $context, ['resource_ids' => $ids]) as $id => $snapshot) {
                if ($context->includeProtectedValues && ! in_array((string) $id, $ids, true)) {
                    throw new RuntimeException('Invalid SDK resource snapshot.');
                }
                $references[$id] = $context->includeProtectedValues
                    ? $this->protectedReference($snapshot, $resourceKey, (string) $id, $context) : $this->reference($snapshot);
            }
        }

        return $references;
    }

    private function executeReferenceIdQuery(string $resourceKey, ResourceReferenceResolutionContext $context, ?string $resourceId, ?array $resourceIds, bool $standard = false): ResolvedResourceReference|array|null
    {
        if ($resourceIds === null) {
            $result = $this->standardRequest('resolve', $resourceKey, $context, ['resource_id' => $resourceId, 'standard_query' => true]);

            return $result === null ? null : $this->standardReference($result, $resourceKey, $context, $resourceId);
        }
        $references = [];
        foreach (array_chunk(array_values(array_unique($resourceIds)), 100) as $ids) {
            $result = $this->standardRequest('resolveMany', $resourceKey, $context, ['resource_ids' => $ids, 'standard_query' => true]);
            if (! is_array($result)) {
                throw new RuntimeException('Invalid reference response.');
            }
            foreach ($result as $id => $snapshot) {
                if (! in_array((string) $id, $ids, true)) {
                    throw new RuntimeException('Invalid reference response.');
                }
                $references[$id] = $this->standardReference($snapshot, $resourceKey, $context, (string) $id);
            }
        }

        return $references;
    }

    public function resolveManyGrouped(string $resourceKey, array $groups, ResourceReferenceResolutionContext $context): array
    {
        $result = [];
        $batch = [];
        $count = 0;
        $flush = function () use ($resourceKey, $context, &$batch, &$count, &$result): void {
            if ($batch === []) {
                return;
            }
            foreach ($this->request('resolveManyGrouped', $resourceKey, $context, ['groups' => $batch]) as $key => $items) {
                if (! isset($batch[$key]) || ! is_array($items)) {
                    throw new RuntimeException('Invalid grouped reference response.');
                }
                foreach ($items as $id => $snapshot) {
                    if (! in_array((string) $id, $batch[$key]['resource_ids'], true)) {
                        throw new RuntimeException('Unexpected grouped reference.');
                    }
                    $reference = $this->reference($snapshot);
                    if ($reference->resourceKey !== $resourceKey || $reference->resourceId !== (string) $id
                        || $reference->asOf->format('U.u') !== (new DateTimeImmutable($batch[$key]['as_of']))->format('U.u')) {
                        throw new RuntimeException('Invalid grouped reference.');
                    }
                    $result[$key][$id] = $reference;
                }
            }
            $batch = [];
            $count = 0;
        };
        $pending = [];
        foreach ($groups as $key => $group) {
            if (! is_string($key) || $key === '' || mb_strlen($key) > 80 || ! is_array($group)
                || ! is_array($group['resource_ids'] ?? null)
                || ! ($group['as_of'] ?? null) instanceof DateTimeImmutable
                || (isset($group['effective_through']) && ! $group['effective_through'] instanceof DateTimeImmutable)) {
                throw new InvalidArgumentException('Invalid grouped reference selector.');
            }
            $asOf = $group['as_of'];
            $through = $group['effective_through'] ?? $asOf;
            $filters = $group['filters'] ?? $context->filters;
            new ResourceReferenceResolutionContext($context->legalEntity, $context->actor, $asOf, $context->purpose,
                $context->operatingUnit, effectiveThrough: $through, filters: $filters);
            foreach ($group['resource_ids'] as $id) {
                if (! is_string($id) || $id === '' || mb_strlen($id) > 255) {
                    throw new InvalidArgumentException('Reference IDs must be bounded strings.');
                }
            }
            $chunks = array_chunk(array_values(array_unique($group['resource_ids'])), 100);
            if ($chunks !== []) {
                $pending[$key] = ['chunks' => $chunks, 'index' => 0, 'as_of' => $asOf->format(DATE_ATOM),
                    'effective_through' => $through->format(DATE_ATOM), 'filters' => $filters];
            }
        }
        while ($pending !== []) {
            foreach ($pending as $key => $selection) {
                $chunk = $selection['chunks'][$selection['index']];
                if (count($batch) >= 31 || $count + count($chunk) > 400 || isset($batch[$key])) {
                    $flush();
                }
                $batch[$key] = ['resource_ids' => $chunk, 'as_of' => $selection['as_of'],
                    'effective_through' => $selection['effective_through'], 'filters' => $selection['filters']];
                $count += count($chunk);
                if (++$pending[$key]['index'] === count($selection['chunks'])) {
                    unset($pending[$key]);
                }
            }
        }
        $flush();

        $ordered = [];
        foreach ($groups as $key => $_) {
            if (isset($result[$key])) {
                $ordered[$key] = $result[$key];
            }
        }

        return $ordered;
    }

    private function executeReferenceGroupedQuery(string $resourceKey, array $groups, ResourceReferenceResolutionContext $context, bool $standard = false, bool $legacyContextFilters = false): array
    {
        $result = [];
        $batch = [];
        $count = 0;
        $strict = ! $legacyContextFilters && ($context->sort !== null
            || array_filter($groups, static fn (array $group): bool => ($group['filters'] ?? []) !== []) !== []);
        $flush = function () use ($resourceKey, $context, $strict, &$batch, &$count, &$result): void {
            if ($batch === []) {
                return;
            }
            $received = $this->request($strict ? 'resolveManyGroupedWithSchema' : 'resolveManyGrouped', $resourceKey, $context, ['groups' => $batch, 'standard_query' => true]);
            if (! is_array($received)) {
                throw new RuntimeException('Invalid grouped reference response.');
            }
            foreach ($received as $key => $items) {
                if (! isset($batch[$key]) || ! is_array($items)) {
                    throw new RuntimeException('Invalid grouped reference response.');
                }
                foreach ($items as $id => $snapshot) {
                    if (! in_array((string) $id, $batch[$key]['resource_ids'], true)) {
                        throw new RuntimeException('Unexpected grouped reference.');
                    }
                    $reference = $this->standardReference($snapshot, $resourceKey, null, (string) $id);
                    if ($reference->resourceKey !== $resourceKey || $reference->resourceId !== (string) $id
                        || $reference->asOf->format('U.u') !== (new DateTimeImmutable($batch[$key]['as_of']))->format('U.u')) {
                        throw new RuntimeException('Invalid grouped reference.');
                    }
                    $result[$key][$id] = $reference;
                }
            }
            $batch = [];
            $count = 0;
        };
        $pending = [];
        foreach ($groups as $key => $group) {
            if (! is_string($key) || $key === '' || mb_strlen($key) > 80 || ! is_array($group)
                || ! is_array($group['resource_ids'] ?? null)
                || ! ($group['as_of'] ?? null) instanceof DateTimeImmutable
                || (isset($group['effective_through']) && ! $group['effective_through'] instanceof DateTimeImmutable)) {
                throw new InvalidArgumentException('Invalid grouped reference selector.');
            }
            $asOf = $group['as_of'];
            $through = $group['effective_through'] ?? $asOf;
            $filters = $group['filters'] ?? $context->filters;
            new ResourceReferenceResolutionContext($context->legalEntity, $context->actor, $asOf, $context->purpose,
                $context->operatingUnit, effectiveThrough: $through, filters: $filters);
            foreach ($group['resource_ids'] as $id) {
                if (! is_string($id) || $id === '' || mb_strlen($id) > 255) {
                    throw new InvalidArgumentException('Reference IDs must be bounded strings.');
                }
            }
            $chunks = array_chunk(array_values(array_unique($group['resource_ids'])), 100);
            if ($chunks !== []) {
                $pending[$key] = ['chunks' => $chunks, 'index' => 0, 'as_of' => $asOf->format(DATE_ATOM),
                    'effective_through' => $through->format(DATE_ATOM), 'filters' => $filters];
            }
        }
        while ($pending !== []) {
            foreach ($pending as $key => $selection) {
                $chunk = $selection['chunks'][$selection['index']];
                if (count($batch) >= 31 || $count + count($chunk) > 400 || isset($batch[$key])) {
                    $flush();
                }
                $batch[$key] = ['resource_ids' => $chunk, 'as_of' => $selection['as_of'],
                    'effective_through' => $selection['effective_through'], 'filters' => $selection['filters']];
                $count += count($chunk);
                if (++$pending[$key]['index'] === count($selection['chunks'])) {
                    unset($pending[$key]);
                }
            }
        }
        $flush();

        $ordered = [];
        foreach ($groups as $key => $_) {
            if (isset($result[$key])) {
                $ordered[$key] = $result[$key];
            }
        }

        return $ordered;
    }

    public function search(string $resourceKey, string $query, ResourceReferenceResolutionContext $context, int $limit = 25): array
    {
        return array_map($this->reference(...), $this->request('search', $resourceKey, $context, ['query' => $query, 'limit' => $limit]));
    }

    public function searchBatch(array $searches): array
    {
        if (! array_is_list($searches)) {
            throw new InvalidArgumentException('Reference searches must be a list.');
        }
        $bodies = [];
        foreach ($searches as $search) {
            if (! is_array($search) || ! is_string($search['resource_key'] ?? null)
                || ! is_string($search['query'] ?? null) || ! ($search['context'] ?? null) instanceof ResourceReferenceResolutionContext
                || ! is_int($search['limit'] ?? 25)) {
                throw new InvalidArgumentException('Invalid reference search.');
            }
            $bodies[] = $this->body('search', $search['resource_key'], $search['context'],
                ['query' => $search['query'], 'limit' => $search['limit'] ?? 25]);
        }
        if ($bodies === []) return [];
        if ($this->sendBatch === null) {
            $results = [];
            foreach ($bodies as $body) {
                $results[] = ['status' => 200, 'result' => ($this->send)($body, $this->reference(...))];
            }
        } else {
            $results = [];
            foreach (array_chunk($bodies, 2) as $chunk) {
                $received = ($this->sendBatch)($chunk);
                if (! is_array($received) || ! array_is_list($received) || count($received) !== count($chunk)) {
                    throw new RuntimeException('Invalid batch reference response.');
                }
                array_push($results, ...$received);
            }
        }
        if (! is_array($results) || ! array_is_list($results) || count($results) !== count($bodies)) {
            throw new RuntimeException('Invalid batch reference response.');
        }
        foreach ($results as &$result) {
            if (! is_array($result) || ! is_int($result['status'] ?? null)
                || ($result['status'] !== 200 && ($result['status'] < 400 || $result['status'] > 599))) {
                throw new RuntimeException('Invalid batch reference response.');
            }
            if ($result['status'] === 200) {
                if (! is_array($result['result'] ?? null) || ! array_is_list($result['result'])) {
                    throw new RuntimeException('Invalid batch reference response.');
                }
                $result = ['status' => 200, 'result' => array_map($this->reference(...), $result['result'])];
            } else {
                $result = ['status' => $result['status']];
            }
        }
        unset($result);
        return $results;
    }

    private function canCombineReferenceSearches(): bool
    {
        return $this->sendBatch !== null;
    }

    /** @return list<array{status:int, result?:array<string, ResolvedResourceReference>}> */
    private function executeReferenceBatchIds(array $requests): array
    {
        $bodies = [];
        foreach ($requests as $item) {
            $bodies[] = $this->body('resolveMany', $item['resource_key'], $item['context'], [
                'resource_ids' => array_values(array_unique($item['options']['ids'])), 'standard_query' => true,
            ]);
        }
        $results = ($this->sendBatch)($bodies);
        if (! is_array($results) || ! array_is_list($results) || count($results) !== count($bodies)) {
            throw new RuntimeException('Invalid batch reference response.');
        }
        foreach ($results as $index => &$result) {
            if (! is_array($result) || ! is_int($result['status'] ?? null)
                || ($result['status'] !== 200 && ($result['status'] < 400 || $result['status'] > 599))) {
                throw new RuntimeException('Invalid batch reference response.');
            }
            if ($result['status'] !== 200) {
                $code = $result['code'] ?? null;
                $expected = match ($code) {
                    'RUNTIME_RESOURCE_READ_NOT_GRANTED' => 503,
                    'RUNTIME_REFERENCE_DEADLINE_EXCEEDED' => 504,
                    'RUNTIME_REFERENCE_CYCLE', 'RUNTIME_REFERENCE_DEPTH_EXCEEDED' => 508,
                    null => null,
                    default => throw new RuntimeException('Invalid batch reference failure.'),
                };
                if ($expected !== null && $expected !== $result['status']) {
                    throw new RuntimeException('Invalid batch reference failure.');
                }
                $result = $code === null ? ['status' => $result['status']] : ['status' => $result['status'], 'code' => $code];
                continue;
            }
            if (! is_array($result['result'] ?? null)) {
                throw new RuntimeException('Invalid batch reference response.');
            }
            $references = [];
            foreach ($result['result'] as $id => $snapshot) {
                $reference = $this->standardReference($snapshot, $bodies[$index]['resource_key'], $requests[$index]['context'], (string) $id);
                if (! in_array((string) $id, $bodies[$index]['resource_ids'], true)
                    || $reference->resourceId !== (string) $id || $reference->resourceKey !== $bodies[$index]['resource_key']
                    || $reference->asOf->format('U.u') !== (new DateTimeImmutable($requests[$index]['context']->asOf->format(DATE_ATOM)))->format('U.u')) {
                    throw new RuntimeException('Invalid batch reference.');
                }
                $references[$id] = $reference;
            }
            $result = ['status' => 200, 'result' => $references];
        }

        return $results;
    }

    private function executeReferenceBatchSearch(array $searches): array
    {
        if (! array_is_list($searches)) {
            throw new InvalidArgumentException('Reference searches must be a list.');
        }
        $bodies = [];
        foreach ($searches as $search) {
            if (! is_array($search) || ! is_string($search['resource_key'] ?? null)
                || ! is_string($search['query'] ?? null) || ! ($search['context'] ?? null) instanceof ResourceReferenceResolutionContext
                || ! is_int($search['limit'] ?? 25)) {
                throw new InvalidArgumentException('Invalid reference search.');
            }
            $bodies[] = $this->body('search', $search['resource_key'], $search['context'],
                ['query' => $search['query'], 'limit' => $search['limit'] ?? 25, 'standard_query' => true]);
        }
        if ($bodies === []) return [];
        if ($this->sendBatch === null) {
            $results = [];
            foreach ($bodies as $body) {
                $results[] = ['status' => 200, 'result' => ($this->send)($body, $this->reference(...))];
            }
        } else {
            $results = [];
            foreach (array_chunk($bodies, 2) as $chunk) {
                $received = ($this->sendBatch)($chunk);
                if (! is_array($received) || ! array_is_list($received) || count($received) !== count($chunk)) {
                    throw new RuntimeException('Invalid batch reference response.');
                }
                array_push($results, ...$received);
            }
        }
        if (! is_array($results) || ! array_is_list($results) || count($results) !== count($bodies)) {
            throw new RuntimeException('Invalid batch reference response.');
        }
        foreach ($results as $index => &$result) {
            if (! is_array($result) || ! is_int($result['status'] ?? null)
                || ($result['status'] !== 200 && ($result['status'] < 400 || $result['status'] > 599))) {
                throw new RuntimeException('Invalid batch reference response.');
            }
            if ($result['status'] === 200) {
                if (! is_array($result['result'] ?? null) || ! array_is_list($result['result'])) {
                    throw new RuntimeException('Invalid batch reference response.');
                }
                $result = ['status' => 200, 'result' => array_map(fn ($item) => $this->standardReference($item, $searches[$index]['resource_key'], $searches[$index]['context']), $result['result'])];
            } else {
                $result = ['status' => $result['status']];
            }
        }
        unset($result);
        return $results;
    }

    public function searchPage(string $resourceKey, string $query, ResourceReferenceResolutionContext $context, int $page = 1, int $perPage = 25): ?ResourceReferencePage
    {
        return $this->page($this->request('searchPage', $resourceKey, $context, ['query' => $query, 'page' => $page, 'per_page' => $perPage]));
    }

    private function executeReferenceSearchQuery(string $resourceKey, ResourceReferenceResolutionContext $context, string $query, ?int $page, ?int $perPage, ?int $limit, bool $standard = false, bool $strictSchema = false): ResourceReferencePage|array|null
    {
        if ($page !== null) {
            return $this->standardPage($this->request($strictSchema ? 'searchPageWithSchema' : 'searchPage', $resourceKey, $context,
                ['query' => $query, 'page' => $page, 'per_page' => $perPage, 'standard_query' => true]),
                $resourceKey, $context, $page, min($perPage, 100));
        }
        $result = $this->request('search', $resourceKey, $context, ['query' => $query, 'limit' => $limit, 'standard_query' => true]);
        if (! is_array($result) || ! array_is_list($result)) {
            throw new RuntimeException('Invalid reference response.');
        }

        return array_map(fn ($item) => $this->standardReference($item, $resourceKey, $context), $result);
    }

    public function searchEffectiveRange(string $resourceKey, array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $limit = 10000): array
    {
        foreach ($queries as $query) {
            if (! is_string($query) || mb_strlen($query) > 160) {
                throw new InvalidArgumentException('Effective-range queries must be strings of at most 160 characters.');
            }
        }
        if ($limit < 1 || $limit > 100000 || $from > $until) {
            throw new InvalidArgumentException('Invalid effective-range search bounds.');
        }
        $result = [];
        foreach (array_chunk(array_values(array_unique($queries)), 100) as $chunk) {
            $snapshots = $this->request('searchEffectiveRange', $resourceKey, $context, [
                'queries' => $chunk, 'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'limit' => $limit,
            ]);
            if (! is_array($snapshots) || count($snapshots) > $limit) {
                throw new RuntimeException('Invalid effective-range response.');
            }
            foreach ($snapshots as $snapshot) {
                $reference = $this->reference($snapshot);
                $key = json_encode([$reference->appKey, $reference->resourceKey, $reference->resourceId,
                    $reference->asOf->format(DATE_ATOM)], JSON_THROW_ON_ERROR);
                $result[$key] ??= $reference;
                if (count($result) >= $limit) {
                    return array_values($result);
                }
            }
        }

        return array_values($result);
    }

    private function executeReferenceEffectiveRangeQuery(string $resourceKey, array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $limit, bool $standard = false): array
    {
        foreach ($queries as $query) {
            if (! is_string($query) || mb_strlen($query) > 160) {
                throw new InvalidArgumentException('Effective-range queries must be strings of at most 160 characters.');
            }
        }
        if ($limit < 1 || $limit > 100000 || $from > $until) {
            throw new InvalidArgumentException('Invalid effective-range search bounds.');
        }
        $result = [];
        foreach (array_chunk(array_values(array_unique($queries)), 100) as $chunk) {
            $snapshots = $this->request('searchEffectiveRange', $resourceKey, $context, [
                'queries' => $chunk, 'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'limit' => $limit, 'standard_query' => true,
            ]);
            if (! is_array($snapshots) || ! array_is_list($snapshots) || count($snapshots) > $limit) {
                throw new RuntimeException('Invalid effective-range response.');
            }
            foreach ($snapshots as $snapshot) {
                $reference = $this->standardReference($snapshot, $resourceKey, null);
                $key = json_encode([$reference->appKey, $reference->resourceKey, $reference->resourceId,
                    $reference->asOf->format(DATE_ATOM)], JSON_THROW_ON_ERROR);
                $result[$key] ??= $reference;
                if (count($result) >= $limit) {
                    return array_values($result);
                }
            }
        }

        return array_values($result);
    }

    public function searchEffectiveRangePage(string $resourceKey, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $page = 1, int $perPage = 500): ?ResourceReferencePage
    {
        return $this->page($this->request('searchEffectiveRangePage', $resourceKey, $context, [
            'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'page' => $page, 'per_page' => $perPage,
        ]));
    }

    public function searchCompleteEffectiveRange(string $resourceKey, array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context): ?array
    {
        if ($queries === [] || count($queries) > 100 || $from > $until) {
            throw new InvalidArgumentException('Invalid complete effective-range search bounds.');
        }
        foreach ($queries as $query) {
            if (! is_string($query) || $query === '' || mb_strlen($query) > 160) {
                throw new InvalidArgumentException('Invalid effective-range search term.');
            }
        }
        $result = $this->request('searchCompleteEffectiveRange', $resourceKey, $context, [
            'queries' => array_values(array_unique($queries)), 'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM),
        ]);
        if ($result === null) {
            return null;
        }
        if (! is_array($result) || ! array_is_list($result) || count($result) > 1000) {
            throw new RuntimeException('Invalid complete effective-range response.');
        }
        $references = [];
        $seen = [];
        foreach ($result as $snapshot) {
            $reference = $this->reference($snapshot);
            if ($reference->resourceKey !== $resourceKey || isset($seen[$reference->resourceId])) {
                throw new RuntimeException('Invalid complete effective-range reference.');
            }
            $seen[$reference->resourceId] = true;
            $references[] = $reference;
        }
        return $references;
    }

    private function executeReferenceCompleteRangeQuery(string $resourceKey, array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context): ?array
    {
        $result = $this->request('searchCompleteEffectiveRange', $resourceKey, $context, [
            'queries' => $queries, 'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'standard_query' => true,
        ]);
        if ($result === null) {
            return null;
        }
        if (! is_array($result) || ! array_is_list($result) || count($result) > 1000) {
            throw new RuntimeException('Invalid complete effective-range response.');
        }
        $references = [];
        $seen = [];
        foreach ($result as $snapshot) {
            $reference = $this->standardReference($snapshot, $resourceKey, null);
            if (isset($seen[$reference->resourceId])) {
                throw new RuntimeException('Invalid complete effective-range reference.');
            }
            $seen[$reference->resourceId] = true;
            $references[] = $reference;
        }

        return $references;
    }

    private function executeReferenceEffectiveRangePageQuery(string $resourceKey, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $page, int $perPage, bool $standard = false): ?ResourceReferencePage
    {
        return $this->standardPage($this->request('searchEffectiveRangePage', $resourceKey, $context, [
            'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'page' => $page, 'per_page' => $perPage, 'standard_query' => true,
        ]), $resourceKey, null, $page, $perPage);
    }

    public function authorized(string $resourceKey, ResourceReferenceResolutionContext $context): bool
    {
        return $this->boolean($this->request('authorized', $resourceKey, $context));
    }

    public function hasProvider(string $resourceKey): bool
    {
        return $this->boolean($this->request('hasProvider', $resourceKey));
    }

    private function boolean(mixed $value): bool
    {
        if (! is_bool($value)) {
            throw new RuntimeException('Invalid SDK read response.');
        }

        return $value;
    }

    private function page(?array $value): ?ResourceReferencePage
    {
        return $value === null ? null : new ResourceReferencePage(
            array_map($this->reference(...), $value['items']), $value['current_page'], $value['last_page'], $value['per_page'], $value['total'],
        );
    }

    private function standardPage(mixed $value, string $resourceKey, ?ResourceReferenceResolutionContext $context, int $page, int $perPage): ?ResourceReferencePage
    {
        if ($value === null) {
            return null;
        }
        if (! is_array($value) || ! is_array($value['items'] ?? null) || ! array_is_list($value['items'])
            || ! is_int($value['current_page'] ?? null) || $value['current_page'] !== $page
            || ! is_int($value['last_page'] ?? null) || ! is_int($value['per_page'] ?? null)
            || $value['per_page'] !== $perPage || ! is_int($value['total'] ?? null)) {
            throw new RuntimeException('Invalid reference page response.');
        }
        $items = array_map(fn ($item) => $this->standardReference($item, $resourceKey, $context), $value['items']);
        try {
            return new ResourceReferencePage($items, $page, $value['last_page'], $perPage, $value['total']);
        } catch (InvalidArgumentException) {
            throw new RuntimeException('Invalid reference page response.');
        }
    }

    private function standardReference(mixed $snapshot, string $resourceKey, ?ResourceReferenceResolutionContext $context, ?string $resourceId = null): ResolvedResourceReference
    {
        if (! is_array($snapshot) && ! $snapshot instanceof ResolvedResourceReference) {
            throw new RuntimeException('Invalid reference response.');
        }
        $reference = $this->reference($snapshot, $context?->includeProtectedValues ?? false);
        $asOf = $context === null ? null : new DateTimeImmutable($context->asOf->format(DATE_ATOM));
        if ($reference->resourceKey !== $resourceKey || ($resourceId !== null && $reference->resourceId !== $resourceId)
            || ($asOf !== null && $reference->asOf->format('U.u') !== $asOf->format('U.u'))) {
            throw new RuntimeException('Invalid reference response.');
        }

        return $reference;
    }

    private function reference(array|ResolvedResourceReference $snapshot, bool $includeProtectedValues = false): ResolvedResourceReference
    {
        try {
            if ($snapshot instanceof ResolvedResourceReference) {
                if ($snapshot->hasProtectedValues() && ! $includeProtectedValues) {
                    throw new RuntimeException;
                }

                return $snapshot;
            }
            $protected = array_key_exists('protected_values', $snapshot) ? $snapshot['protected_values'] : [];
            if (array_key_exists('protectedValues', $snapshot)
                || (array_key_exists('protected_values', $snapshot) && ! $includeProtectedValues)
                || ! is_array($protected)) {
                throw new RuntimeException;
            }
            unset($snapshot['protected_values']);
            $reference = ResolvedResourceReference::fromSnapshot($snapshot);
            if ($protected === []) {
                return $reference;
            }

            return new ResolvedResourceReference($reference->appKey, $reference->resourceKey, $reference->resourceId,
                $reference->display, $reference->href, $reference->fields, $reference->asOf,
                $reference->revision, $reference->state, protectedValues: $protected);
        } catch (Throwable) {
            throw new RuntimeException('Invalid SDK resource snapshot.');
        }
    }

    private function standardRequest(string $method, string $resource, ResourceReferenceResolutionContext $context, array $arguments): mixed
    {
        if (! $context->includeProtectedValues) {
            return $this->request($method, $resource, $context, $arguments);
        }

        return ($this->send)($this->body($method, $resource, $context, $arguments), fn ($snapshot) => $this->reference($snapshot, true));
    }

    private function protectedReference(mixed $snapshot, string $resourceKey, string $resourceId, ResourceReferenceResolutionContext $context): ResolvedResourceReference
    {
        if (! is_array($snapshot) && ! $snapshot instanceof ResolvedResourceReference) {
            throw new RuntimeException('Invalid SDK resource snapshot.');
        }
        $reference = $this->reference($snapshot, true);
        $asOf = new DateTimeImmutable($context->asOf->format(DATE_ATOM));
        if ($reference->resourceKey !== $resourceKey || $reference->resourceId !== $resourceId
            || $reference->asOf->format('U.u') !== $asOf->format('U.u')) {
            throw new RuntimeException('Invalid SDK resource snapshot.');
        }

        return $reference;
    }

    private function request(string $method, string $resource, ?ResourceReferenceResolutionContext $context = null, array $arguments = []): mixed
    {
        return ($this->send)($this->body($method, $resource, $context, $arguments), $this->reference(...));
    }

    private function body(string $method, string $resource, ?ResourceReferenceResolutionContext $context = null, array $arguments = []): array
    {
        if ($context?->includeProtectedValues && ! in_array($method, ['resolve', 'resolveMany'], true)) {
            throw new RuntimeException('Protected values are available only for exact reference reads.');
        }
        $body = ['method' => $method, 'resource_key' => $resource, ...$arguments];
        if ($context?->includeProtectedValues) {
            $body['standard_query'] = true;
        }
        if ($context !== null) {
            $body['context'] = [
                'actor_public_id' => $context->actor->publicId(), 'legal_entity_public_id' => $context->legalEntity?->publicId(),
                'operating_unit_public_id' => $context->operatingUnit?->publicId(), 'as_of' => $context->asOf->format(DATE_ATOM),
                'purpose' => $context->purpose, 'filters' => $context->filters, 'sort' => $context->sort,
                'effective_through' => $context->effectiveThrough?->format(DATE_ATOM),
                ...($context->includeProtectedValues ? ['include_protected_values' => true] : []),
            ];
        }
        return $body;
    }

}
