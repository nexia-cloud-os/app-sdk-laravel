<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access;

use LogicException;
use Nexia\Laravel\Access\Contracts\PermissionAuthorizer;

final class PermissionAuthorizerResolver
{
    private static ?PermissionAuthorizer $authorizer = null;

    public static function configure(PermissionAuthorizer $authorizer): void
    {
        self::$authorizer = $authorizer;
    }

    public static function resetForTests(): void
    {
        self::$authorizer = null;
    }

    public static function resolve(): PermissionAuthorizer
    {
        return self::$authorizer
            ?? throw new LogicException('Permission authorizer has not been configured.');
    }
}
