<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional transport for independent, owner-authorized searches. */
interface BatchSearchResourceReferences extends ResourceReferences
{
    /**
     * @param list<array{resource_key:string, query:string, context:ResourceReferenceResolutionContext, limit?:int}> $searches
     * @return list<array{status:int, result?:list<\Nexia\ResourceReference\ResolvedResourceReference>}>
     */
    public function searchBatch(array $searches): array;
}
