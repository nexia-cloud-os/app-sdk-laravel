<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventPublicationReceipt;

/**
 * Host-governed registry for a public Event producer to re-emit its current
 * state during consumer recovery.
 *
 * The producer callback must yield only receipts created by the registered
 * public Event. The host synchronously relays every yielded publication before
 * reconcile() returns, allowing the recovering consumer to verify its local
 * projection before its recovery generation is finalized.
 */
interface EventCurrentStateReconcilers
{
    /**
     * @param  callable(): iterable<EventPublicationReceipt>  $reconciler
     */
    public function register(string $ownerAppKey, string $eventName, callable $reconciler): void;

    /** Return the number of publications synchronously relayed. */
    public function reconcile(string $eventName): int;
}
