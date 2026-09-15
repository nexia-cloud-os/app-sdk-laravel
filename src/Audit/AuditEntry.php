<?php

declare(strict_types=1);

namespace Nexia\Audit;

use DateTimeImmutable;

/** Durable audit fact submitted by an App to the Core-owned audit trail. */
final readonly class AuditEntry
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $event,
        public int|string|null $actorKey,
        public string $actorType,
        public int|string|null $legalEntityKey,
        public ?string $ipAddress,
        public array $payload,
        public DateTimeImmutable $occurredAt,
    ) {}
}
