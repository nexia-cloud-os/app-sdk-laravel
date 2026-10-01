<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use Closure;
use DateTimeImmutable;
use Nexia\ResourceReference\Contracts\ResourceReferences;
use RuntimeException;
use Throwable;

/** Existing Resource Reference wire contract over a host-owned bounded transport. */
readonly class TransportResourceReferences implements ResourceReferences
{
    public function __construct(private Closure $send) {}

    public function resolve(string $resourceKey, string $resourceId, ResourceReferenceResolutionContext $context): ?ResolvedResourceReference
    {
        $result = $this->request('resolve', $resourceKey, $context, ['resource_id' => $resourceId]);

        return $result === null ? null : $this->reference($result);
    }

    public function resolveMany(string $resourceKey, array $resourceIds, ResourceReferenceResolutionContext $context): array
    {
        if ($resourceIds === []) {
            return [];
        }

        return array_map($this->reference(...), $this->request('resolveMany', $resourceKey, $context, ['resource_ids' => $resourceIds]));
    }

    public function search(string $resourceKey, string $query, ResourceReferenceResolutionContext $context, int $limit = 25): array
    {
        return array_map($this->reference(...), $this->request('search', $resourceKey, $context, ['query' => $query, 'limit' => $limit]));
    }

    public function searchPage(string $resourceKey, string $query, ResourceReferenceResolutionContext $context, int $page = 1, int $perPage = 25): ?ResourceReferencePage
    {
        return $this->page($this->request('searchPage', $resourceKey, $context, ['query' => $query, 'page' => $page, 'per_page' => $perPage]));
    }

    public function searchEffectiveRange(string $resourceKey, array $queries, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $limit = 10000): array
    {
        if ($queries === []) {
            return [];
        }

        return array_map($this->reference(...), $this->request('searchEffectiveRange', $resourceKey, $context, [
            'queries' => $queries, 'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'limit' => $limit,
        ]));
    }

    public function searchEffectiveRangePage(string $resourceKey, DateTimeImmutable $from, DateTimeImmutable $until, ResourceReferenceResolutionContext $context, int $page = 1, int $perPage = 500): ?ResourceReferencePage
    {
        return $this->page($this->request('searchEffectiveRangePage', $resourceKey, $context, [
            'from' => $from->format(DATE_ATOM), 'until' => $until->format(DATE_ATOM), 'page' => $page, 'per_page' => $perPage,
        ]));
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

    private function reference(array $snapshot): ResolvedResourceReference
    {
        try {
            if (($snapshot['schema_version'] ?? null) !== 1 || array_key_exists('protected_values', $snapshot) || array_key_exists('protectedValues', $snapshot)) {
                throw new RuntimeException;
            }
            $reference = $snapshot['reference'];
            $result = new ResolvedResourceReference(
                $reference['app_key'], $reference['resource_key'], $reference['resource_id'], $reference['display'], $reference['href'],
                $snapshot['fields'], new DateTimeImmutable($snapshot['as_of']), $snapshot['revision'], $snapshot['status'],
            );
            if (! hash_equals($result->snapshot()['content_hash'], $snapshot['content_hash'])) {
                throw new RuntimeException;
            }

            return $result;
        } catch (Throwable) {
            throw new RuntimeException('Invalid SDK resource snapshot.');
        }
    }

    private function request(string $method, string $resource, ?ResourceReferenceResolutionContext $context = null, array $arguments = []): mixed
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
        return ($this->send)($body);
    }

}
