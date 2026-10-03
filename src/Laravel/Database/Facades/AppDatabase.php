<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database\Facades;

use Illuminate\Database\Connection;
use LogicException;
use Nexia\Laravel\Database\Contracts\AppDatabaseConnections;

/** Base for a package-local `Support\\DB` facade with an immutable App key. */
abstract class AppDatabase
{
    public static function connection(?string $requested = null): Connection
    {
        if ($requested !== null) {
            throw new LogicException('App database facades cannot select another connection.');
        }

        return app(AppDatabaseConnections::class)->connection(static::appKey());
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::connection()->{$method}(...$arguments);
    }

    private static function appKey(): string
    {
        $key = defined(static::class.'::APP_KEY') ? constant(static::class.'::APP_KEY') : null;
        if (! is_string($key) || $key === '') {
            throw new LogicException('App database facade must declare a non-empty APP_KEY.');
        }

        return $key;
    }
}
