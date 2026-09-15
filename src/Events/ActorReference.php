<?php

declare(strict_types=1);

namespace Nexia\Events;

use InvalidArgumentException;

/** App-supplied actor identity that Core re-resolves and re-authorizes. */
final readonly class ActorReference
{
    public function __construct(
        public ?string $actorId = null,
        public ?string $actorType = null,
    ) {
        if ($actorId === null && $actorType !== null) {
            throw new InvalidArgumentException('An actor type requires an actor id.');
        }

        if ($actorId !== null && ($actorId === '' || $actorId !== trim($actorId))) {
            throw new InvalidArgumentException('An actor id must be null or a normalized non-blank string.');
        }

        if ($actorType !== null && ($actorType === '' || $actorType !== trim($actorType))) {
            throw new InvalidArgumentException('An actor type must be null or a normalized non-blank string.');
        }
    }
}
