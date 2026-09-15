<?php

declare(strict_types=1);

namespace Nexia\OfficialSeal;

/** Server-only immutable seal asset acquired for one generated output. */
final readonly class OfficialSealAsset
{
    public function __construct(
        public string $usageId,
        public string $publicId,
        public string $name,
        public string $kind,
        public int $version,
        public string $mimeType,
        public string $bytes,
        public string $sourceChecksum,
    ) {}
}
