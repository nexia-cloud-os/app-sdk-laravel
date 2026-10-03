<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database\Contracts;

use Illuminate\Database\Connection;

/** Resolves the currently authorized App-owned database connection. */
interface AppDatabaseConnections
{
    /** The caller supplies a declared App key; implementations never switch Laravel's default connection. */
    public function connection(string $appKey): Connection;

    /** Returns the owned named connection for an App model, or null for a host model. */
    public function modelConnectionName(string $modelClass): ?string;
}
