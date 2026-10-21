<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use DateTimeImmutable;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional grouped resolution capability; existing ResourceReferences implementations remain compatible. */
interface GroupedResourceReferences extends ResourceReferences
{
    /**
     * Resolve bounded groups under one actor and organization scope. Each group
     * may select its own as-of time and owner-defined filters; missing or denied
     * references are omitted. Filter names and authorization belong to the owner.
     *
     * @param array<string, array{resource_ids:list<string>, as_of:DateTimeImmutable, filters?:array<string, string|list<string>>, effective_through?:DateTimeImmutable}> $groups
     * @return array<string, array<string, ResolvedResourceReference>>
     */
    public function resolveManyGrouped(string $resourceKey, array $groups, ResourceReferenceResolutionContext $context): array;
}
