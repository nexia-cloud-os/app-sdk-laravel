<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Concerns;

use Nexia\Laravel\Database\AppDatabaseConnectionsResolver;

/** Opt-in model connection selection for code declared by an installed App package. */
trait UsesAppDatabaseConnection
{
    public function getConnectionName(): ?string
    {
        return AppDatabaseConnectionsResolver::resolve()->modelConnectionName(static::class)
            ?? parent::getConnectionName();
    }
}
