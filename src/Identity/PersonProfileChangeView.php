<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** One immutable, presentation-safe change to a Core-owned person profile. */
final readonly class PersonProfileChangeView
{
    /**
     * @param  list<array{field: string, before: ?string, after: ?string}>  $changes
     */
    public function __construct(
        public string $occurredAt,
        public ?string $actorLabel,
        public string $reason,
        public array $changes,
    ) {}
}
