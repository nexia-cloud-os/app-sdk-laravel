<?php

declare(strict_types=1);

namespace Nexia\Dashboard\Contracts;

/**
 * An App's existing collection accepts legal_entity_public_ids before paging,
 * preserves its own row policy and returns the declared scope fields on every row.
 * This declaration does not grant access or register an additional route.
 */
interface DashboardScopedResourceCollection
{
    /** Exact existing collection GET route published by the App. */
    public static function dashboardCollectionRoute(): string;

    public static function dashboardCollectionLegalEntityField(): string;

    /** Null only when the collection has no Operating Unit-owned rows. */
    public static function dashboardCollectionOperatingUnitField(): ?string;
}
