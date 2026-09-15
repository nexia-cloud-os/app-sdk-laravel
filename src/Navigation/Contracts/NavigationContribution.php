<?php

declare(strict_types=1);

namespace Nexia\Navigation\Contracts;

use Nexia\Navigation\NavigationItem;

/**
 * Marks an App class as a shell-navigation contributor.
 *
 * Core discovers and aggregates implementations; Apps only publish the
 * stable navigation entry shape through this contract.
 */
interface NavigationContribution
{
    /**
     * @return list<NavigationItem>
     */
    public static function navigationItems(): array;
}
