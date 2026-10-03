<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database\Facades;

use Illuminate\Database\Schema\Builder;
use LogicException;
use Nexia\Laravel\Database\AppDatabaseConnectionsResolver;

/** Base for a package-local `Support\\Schema` facade with an immutable App key. */
abstract class AppSchema
{
    public static function connection(?string $requested = null): Builder
    {
        if ($requested !== null) {
            throw new LogicException('App schema facades cannot select another connection.');
        }

        return AppDatabaseConnectionsResolver::resolve()->connection(static::appKey())->getSchemaBuilder();
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::connection()->{$method}(...$arguments);
    }

    private static function appKey(): string
    {
        $key = defined(static::class.'::APP_KEY') ? constant(static::class.'::APP_KEY') : null;
        if (! is_string($key) || $key === '') {
            throw new LogicException('App schema facade must declare a non-empty APP_KEY.');
        }

        return $key;
    }
}
