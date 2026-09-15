<?php

declare(strict_types=1);

namespace Nexia\Attachments;

/** Durable, authorized import-file identity without exposing bytes or storage coordinates. */
final readonly class AuthorizedImportFile
{
    public function __construct(
        public string $publicId,
        public string $originalName,
        public string $mimeType,
        public ?string $checksum,
        public int $sizeBytes,
        public int|string|null $uploadedByKey = null,
    ) {}
}
