<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Concerns;

use Nexia\Laravel\Database\Contracts\AppDatabaseConnections;

/** Opt-in model connection selection for code declared by an installed App package. */
trait UsesAppDatabaseConnection
{
    public function getConnectionName(): ?string
    {
        return app(AppDatabaseConnections::class)->modelConnectionName(static::class)
            ?? parent::getConnectionName();
    }
}
