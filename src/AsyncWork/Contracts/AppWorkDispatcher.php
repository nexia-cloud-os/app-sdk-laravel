<?php

declare(strict_types=1);

namespace Nexia\AsyncWork\Contracts;

/**
 * Enqueues a declared App work key for the App selected by the current runtime.
 *
 * The host resolves the App, tenant, deployment generation, actor and scope;
 * Apps may never select a handler class or an execution target directly.
 */
interface AppWorkDispatcher
{
    /** @param array<string, mixed> $payload */
    public function dispatch(string $key, array $payload, string $idempotencyKey): void;
}
