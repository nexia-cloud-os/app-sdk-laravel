<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access;

use Closure;
use LogicException;
use Nexia\Laravel\Access\Contracts\PermissionAuthorizer;

final class PermissionAuthorizerResolver
{
    private static PermissionAuthorizer|Closure|null $authorizer = null;

    public static function configure(PermissionAuthorizer|Closure $authorizer): void
    {
        self::$authorizer = $authorizer;
    }

    public static function resetForTests(): void
    {
        self::$authorizer = null;
    }

    public static function resolve(): PermissionAuthorizer
    {
        return (self::$authorizer instanceof Closure ? (self::$authorizer)() : self::$authorizer)
            ?? throw new LogicException('Permission authorizer has not been configured.');
    }
}
