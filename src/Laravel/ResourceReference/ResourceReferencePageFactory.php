<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceReference;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceReferencePage;

final class ResourceReferencePageFactory
{
    /** @param Closure(mixed): ResolvedResourceReference $map */
    public static function fromPaginator(LengthAwarePaginator $paginator, Closure $map): ResourceReferencePage
    {
        return new ResourceReferencePage(
            items: array_values(array_map($map, $paginator->items())),
            currentPage: $paginator->currentPage(),
            lastPage: max(1, $paginator->lastPage()),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }
}
