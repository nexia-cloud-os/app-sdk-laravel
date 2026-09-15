<?php

declare(strict_types=1);

namespace Nexia\Tenancy\Contracts;

/** Core-owned tenant iterator that restores and clears tenant context around each callback. */
interface TenantRunner
{
    /**
     * Restore one tenant around a callback.
     *
     * @param  callable(TenantIdentity): void  $callback
     * @return bool False when the tenant no longer exists.
     */
    public function runFor(int|string $tenantKey, callable $callback): bool;

    /**
     * @param  callable(TenantIdentity): void  $callback
     * @return int Number of tenants presented to the callback.
     */
    public function runForAll(callable $callback): int;
}
