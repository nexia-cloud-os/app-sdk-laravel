<?php

declare(strict_types=1);

namespace Nexia\AsyncWork\Contracts;

use Nexia\AsyncWork\BackgroundOperationReservation;
use Nexia\Identity\Contracts\Actor;

/** Transient, actor-owned result channel for App background work. */
interface BackgroundOperationStore
{
    public function open(Actor $actor, string $phase = 'queued'): string;

    /** Reserve the actor's one apply slot; an identical retry returns its existing id. */
    public function reserve(
        Actor $actor,
        string $identity,
        string $phase = 'queued',
    ): BackgroundOperationReservation;

    public function progress(string $operationId, string $phase, int $percent): void;

    /** @param array<string, mixed> $result */
    public function complete(string $operationId, array $result): void;

    /** @param array<string, list<string>|array<string, mixed>> $errors */
    public function fail(string $operationId, array $errors): void;

    /** @param array<string, list<string>|array<string, mixed>> $errors */
    public function failIfPending(string $operationId, array $errors): void;
}
