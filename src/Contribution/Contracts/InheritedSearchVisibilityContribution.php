<?php

declare(strict_types=1);

namespace Nexia\Contribution\Contracts;

/**
 * A resource whose search visibility is inherited from a related host record.
 *
 * Core resolves and enforces the relation; Apps declare only its stable name.
 */
interface InheritedSearchVisibilityContribution extends ResourceCatalogContribution
{
    public static function inheritedSearchVisibilityRelation(): string;
}
