<?php

declare(strict_types=1);

namespace Nexia\Attachments;

/** Metadata for bytes an App already wrote to tenant-scoped storage. */
final readonly class GeneratedFile
{
    public function __construct(
        public int|string $uploadedByKey,
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public string $checksum,
        public int|string|null $legalEntityKey = null,
    ) {}
}
