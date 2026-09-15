<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventConsumerRegistration;
use Nexia\Events\EventEnvelope;

/**
 * Host-governed registration surface for App-owned outbox event listeners.
 *
 * Registration is global and side-effect free: an App Package provider calls
 * listen() during boot without reading any tenant state. The host defers the
 * activation decision to execution time — a registered listener is invoked
 * only when the owning App is operational (active, initialized, and with all
 * transitive prerequisites operational) for the tenant of the dispatched
 * event. Events dispatched without an initialized tenant context never reach
 * App-owned listeners.
 *
 * The owning App is declared explicitly via $ownerAppKey; the host never
 * infers ownership from a listener's namespace.
 */
interface AppEventListeners
{
    /**
     * Register a tenant-gated listener for one or more outbox events.
     *
     * @param  string  $ownerAppKey  app key whose tenant activation gates execution
     * @param  string|list<string>  $events  full event names such as
     *                                       "outbox:approval.case.completed" in exact mode; an empty list is
     *                                       permitted only for an all-public-events consumer registration
     * @param  (callable(EventEnvelope): void)|class-string|array{class-string, string}  $listener
     *                                                                                              a closure receiving the envelope, a class-string whose handle()
     *                                                                                              method is invoked, or a [class-string, method] pair resolved
     *                                                                                              through the container at execution time
     * @param  null|callable(): void  $reconciler  idempotent current-state rebuild
     *                                             required only for reconcile recovery
     */
    public function listen(
        string $ownerAppKey,
        string|array $events,
        callable|string|array $listener,
        ?EventConsumerRegistration $consumer = null,
        ?callable $reconciler = null,
    ): void;
}
