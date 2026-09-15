<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** App-neutral, non-protected person profile owned by the host Party ledger. */
final readonly class PersonProfileView
{
    /** @param array<string, string> $alternateNames @param array<string, mixed>|null $address */
    public function __construct(
        public string $partyPublicId,
        public string $displayName,
        public ?string $legalName,
        public array $alternateNames,
        public ?string $contactEmail,
        public ?string $contactPhone,
        public ?array $address,
        public string $version,
    ) {}
}
