<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** Safe Party relationship fields exposed without leaking the host model. */
final readonly class PartyRelationship
{
    public function __construct(
        public string $publicId,
        public string $type,
        public int|string $fromPartyKey,
        public string $toPartyPublicId,
        public string $toPartyDisplayLabel,
        public ?string $title,
        public bool $active,
    ) {}
}
