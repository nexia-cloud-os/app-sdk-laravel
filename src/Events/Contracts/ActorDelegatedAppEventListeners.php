<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventConsumerRegistration;

interface ActorDelegatedAppEventListeners
{
    /**
     * Register an App listener that runs only after the envelope actor is
     * restored and the supplied authorizer approves at execution time.
     *
     * @param  string|list<string>  $events  exact full event names, or an empty
     *                                       list only for an all-public-events consumer registration
     * @param  callable|string|array{class-string, string}  $listener
     * @param  class-string<EventActorAuthorizer>  $authorizer
     * @param  null|callable(): void  $reconciler
     */
    public function listenDelegated(
        string $ownerAppKey,
        string|array $events,
        callable|string|array $listener,
        string $authorizer,
        ?EventConsumerRegistration $consumer = null,
        ?callable $reconciler = null,
    ): void;
}
