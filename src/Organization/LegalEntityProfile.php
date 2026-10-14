<?php

declare(strict_types=1);

namespace Nexia\Organization;

/** Registered organization facts; never a signatory or authorization decision. */
final readonly class LegalEntityProfile
{
    /** @param array<string, mixed>|null $registeredAddress */
    public function __construct(
        public ?string $legalName,
        public ?string $businessNumber,
        public ?string $representativeName,
        public ?array $registeredAddress,
    ) {}
}
