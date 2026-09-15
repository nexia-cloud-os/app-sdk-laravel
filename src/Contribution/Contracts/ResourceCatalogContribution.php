<?php

declare(strict_types=1);

namespace Nexia\Contribution\Contracts;

/**
 * Binds a stable resource key to an App-owned Eloquent model.
 *
 * Core discovers providers and owns runtime aggregation; Apps publish only
 * this identity contract.
 */
interface ResourceCatalogContribution
{
    public static function resourceKey(): string;

    /** @return class-string */
    public static function resourceModelClass(): string;
}
