<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models;

use LogicException;
use Nexia\Laravel\Models\Contracts\ScoutSearchEngineResolver;
use Nexia\Laravel\Models\Contracts\SearchResourceIdentityResolver;

/**
 * SDK-wide host configuration for the Scout model adapter.
 *
 * Trait static properties are copied onto every consuming model class, so the
 * host configuration must live outside the trait to apply uniformly to Core
 * and App models alike.
 */
final class ScoutSearchResolverRegistry
{
    private static ?SearchResourceIdentityResolver $identityResolver = null;

    private static ?ScoutSearchEngineResolver $engineResolver = null;

    public static function configure(
        SearchResourceIdentityResolver $identityResolver,
        ScoutSearchEngineResolver $engineResolver,
    ): void {
        self::$identityResolver = $identityResolver;
        self::$engineResolver = $engineResolver;
    }

    public static function identityResolver(): ?SearchResourceIdentityResolver
    {
        return self::$identityResolver;
    }

    public static function engineResolver(): ScoutSearchEngineResolver
    {
        if (self::$engineResolver === null) {
            throw new LogicException('Scout search engine resolver is not configured.');
        }

        return self::$engineResolver;
    }
}
