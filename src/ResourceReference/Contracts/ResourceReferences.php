<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferencePage;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Core-governed dispatch to owner-authorized App resource reference providers. */
interface ResourceReferences
{
    public function resolve(
        string $resourceKey,
        string $resourceId,
        ResourceReferenceResolutionContext $context,
    ): ?ResolvedResourceReference;

    /**
     * @param  list<string>  $resourceIds
     * @return array<string, ResolvedResourceReference> References keyed by resource id.
     */
    public function resolveMany(
        string $resourceKey,
        array $resourceIds,
        ResourceReferenceResolutionContext $context,
    ): array;

    /** @return list<ResolvedResourceReference> */
    public function search(
        string $resourceKey,
        string $query,
        ResourceReferenceResolutionContext $context,
        int $limit = 25,
    ): array;

    public function searchPage(
        string $resourceKey,
        string $query,
        ResourceReferenceResolutionContext $context,
        int $page = 1,
        int $perPage = 25,
    ): ?ResourceReferencePage;

    /**
     * Owner-defined exact search across an effective-date range. An empty
     * query list returns no results. Providers without the optional batch
     * capability also return an empty list.
     *
     * @param  list<string>  $queries
     * @return list<ResolvedResourceReference>
     */
    public function searchEffectiveRange(
        string $resourceKey,
        array $queries,
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        ResourceReferenceResolutionContext $context,
        int $limit = 10_000,
    ): array;

    /** Complete authorized population overlapping an effective-date range, when supported. */
    public function searchEffectiveRangePage(
        string $resourceKey,
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        ResourceReferenceResolutionContext $context,
        int $page = 1,
        int $perPage = 500,
    ): ?ResourceReferencePage;

    public function authorized(string $resourceKey, ResourceReferenceResolutionContext $context): bool;

    public function hasProvider(string $resourceKey): bool;
}
