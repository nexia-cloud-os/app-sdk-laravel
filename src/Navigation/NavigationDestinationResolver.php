<?php

declare(strict_types=1);

namespace Nexia\Navigation;

use LogicException;
use Nexia\Navigation\Contracts\NavigationDestinations;

final class NavigationDestinationResolver
{
    private static ?NavigationDestinations $destinations = null;

    public static function configure(NavigationDestinations $destinations): void
    {
        self::$destinations = $destinations;
    }

    public static function resetForTests(): void
    {
        self::$destinations = null;
    }

    public static function resolve(): NavigationDestinations
    {
        return self::$destinations
            ?? throw new LogicException('Navigation destinations have not been configured.');
    }
}
