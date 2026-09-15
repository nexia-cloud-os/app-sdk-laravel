<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Contracts;

use Laravel\Scout\Engines\Engine;

/**
 * Host-owned Scout engine selection for SDK Laravel models.
 *
 * The SDK owns which search lane a model requires; the host owns resolving
 * the configured Laravel Scout engines from its container.
 */
interface ScoutSearchEngineResolver
{
    public function defaultEngine(): Engine;

    public function databaseEngine(): Engine;
}
