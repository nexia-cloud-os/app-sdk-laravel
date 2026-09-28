<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

/** Optional discovery of the conditions implemented by the owning paged provider. */
interface ResourceReferenceQuerySchemaContribution extends ResourceReferencePagedSearchContribution
{
    /**
     * Filters accept bounded strings: OR within one field, AND across fields.
     * Sort accepts one field, prefixed with '-' for descending order.
     * Only advertise public projection fields the provider actually supports.
     * This metadata never grants row or field access.
     *
     * @return array{filterable: list<string>, sortable: list<string>, searchable: list<string>}
     */
    public function querySchema(): array;
}
