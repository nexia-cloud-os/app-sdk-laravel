<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResourceReferencePage;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Selector-grade owner search with pagination and a type-level authorization probe. */
interface ResourceReferencePagedSearchContribution extends ResourceReferenceSearchContribution
{
    public function authorized(ResourceReferenceResolutionContext $context): bool;

    public function searchPage(
        string $query,
        ResourceReferenceResolutionContext $context,
        int $page,
        int $perPage,
    ): ResourceReferencePage;
}
