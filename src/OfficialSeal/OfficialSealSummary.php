<?php

declare(strict_types=1);

namespace Nexia\OfficialSeal;

/** Metadata safe to expose to an App settings surface. The source image is deliberately absent. */
final readonly class OfficialSealSummary
{
    public function __construct(
        public string $publicId,
        public string $name,
        public string $kind,
        public int $version,
        public string $status,
        public ?string $validFrom,
        public ?string $validUntil,
    ) {}
}
