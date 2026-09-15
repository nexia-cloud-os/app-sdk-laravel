<?php

declare(strict_types=1);

namespace Nexia\Navigation\Concerns;

use Nexia\Navigation\NavigationDestinationResolver;

/** Adapts an App resource catalog's navigation declaration through the host resolver. */
trait ContributesNavigationDestination
{
    public static function navigationDestinationRoute(): string
    {
        return NavigationDestinationResolver::resolve()->route(static::class);
    }

    public static function navigationDestinationIcon(): string
    {
        return NavigationDestinationResolver::resolve()->icon(static::class);
    }

    public static function navigationDestinationPermissionKey(): ?string
    {
        return NavigationDestinationResolver::resolve()->permissionKey(static::class);
    }

    /** @return list<array<string, mixed>> */
    public static function navigationItems(): array
    {
        return NavigationDestinationResolver::resolve()->items(static::class);
    }
}
