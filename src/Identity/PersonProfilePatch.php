<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** Explicit host-owned person fields; omitted values are represented by the caller's current value. */
final readonly class PersonProfilePatch
{
    /** @param array<string, string> $alternateNames @param array<string, string>|null $address */
    public function __construct(
        public string $displayName,
        public ?string $legalName,
        public array $alternateNames,
        public ?string $contactEmail,
        public ?string $contactPhone,
        public ?array $address,
        public string $expectedVersion,
        public string $reason,
    ) {}
}
