<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\ResourceReference\Contracts\BatchSearchResourceReferences;
use Nexia\ResourceReference\Contracts\CompleteEffectiveRangeReferences;
use Nexia\ResourceReference\Contracts\GroupedResourceReferences;
use RuntimeException;
use Throwable;

/** Existing Resource Reference wire contract over a host-owned bounded transport. */
readonly class TransportResourceReferences implements GroupedResourceReferences, BatchSearchResourceReferences, CompleteEffectiveRangeReferences
{
    /** The host may use the optional second callback to decode public snapshots once. */
    public function __construct(private Closure $send, private ?Closure $sendBatch = null) {}

    public function resolve(string $resourceKey, string $resourceId, ResourceReferenceResolutionContext $context): ?ResolvedResourceReference
    {
        $result = $this->request('resolve', $resourceKey, $context, ['resource_id' => $resourceId]);

        return $result === null ? null : $this->reference($result);
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
                $references[$id] = $this->reference($snapshot);
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

    public function searchPage(string $resourceKey, string $query, ResourceReferenceResolutionContext $context, int $page = 1, int $perPage = 25): ?ResourceReferencePage
    {
        return $this->page($this->request('searchPage', $resourceKey, $context, ['query' => $query, 'page' => $page, 'per_page' => $perPage]));
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

    private function reference(array|ResolvedResourceReference $snapshot): ResolvedResourceReference
    {
        try {
            // Host transports may reuse an already validated public reference.
            if ($snapshot instanceof ResolvedResourceReference) {
                if ($snapshot->hasProtectedValues()) {
                    throw new RuntimeException;
                }

                return $snapshot;
            }

            return ResolvedResourceReference::fromSnapshot($snapshot);
        } catch (Throwable) {
            throw new RuntimeException('Invalid SDK resource snapshot.');
        }
    }

    private function request(string $method, string $resource, ?ResourceReferenceResolutionContext $context = null, array $arguments = []): mixed
    {
        return ($this->send)($this->body($method, $resource, $context, $arguments), $this->reference(...));
    }

    private function body(string $method, string $resource, ?ResourceReferenceResolutionContext $context = null, array $arguments = []): array
    {
        if ($context?->includeProtectedValues) {
            throw new RuntimeException('Development read probes do not expose protected values.');
        }
        $body = ['method' => $method, 'resource_key' => $resource, ...$arguments];
        if ($context !== null) {
            $body['context'] = [
                'actor_public_id' => $context->actor->publicId(), 'legal_entity_public_id' => $context->legalEntity?->publicId(),
                'operating_unit_public_id' => $context->operatingUnit?->publicId(), 'as_of' => $context->asOf->format(DATE_ATOM),
                'purpose' => $context->purpose, 'filters' => $context->filters, 'sort' => $context->sort,
                'effective_through' => $context->effectiveThrough?->format(DATE_ATOM),
            ];
        }
        return $body;
    }

}
