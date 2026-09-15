<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** One cursor page of presentation-safe person-profile changes. */
final readonly class PersonProfileHistoryPage
{
    /** @param list<PersonProfileChangeView> $items */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {}
}
