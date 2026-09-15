<?php

declare(strict_types=1);

namespace Nexia\AsyncWork;

/** Atomic actor-exclusive background-operation reservation. */
final readonly class BackgroundOperationReservation
{
    public function __construct(
        public string $operationId,
        public bool $acquired,
        public bool $matchesIdentity,
    ) {}
}
