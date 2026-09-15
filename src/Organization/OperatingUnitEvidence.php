<?php

declare(strict_types=1);

namespace Nexia\Organization;

/** Immutable public evidence for an Operating Unit used in App-owned decisions. */
final readonly class OperatingUnitEvidence
{
    public function __construct(
        public int|string $key,
        public string $publicId,
        public string $displayLabel,
        public int $revision,
        public string $code,
        public string $name,
        public ?string $kind,
    ) {}
}
