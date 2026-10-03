<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database;

use Closure;
use LogicException;
use Nexia\Laravel\Database\Contracts\AppDatabaseConnections;

/** Evaluate host wiring on every call, as with the authorization resolver. */
final class AppDatabaseConnectionsResolver
{
    private static ?Closure $resolver = null;

    public static function configure(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function resolve(): AppDatabaseConnections
    {
        return self::$resolver !== null
            ? (self::$resolver)()
            : throw new LogicException('App database connections have not been configured by the host.');
    }
}
