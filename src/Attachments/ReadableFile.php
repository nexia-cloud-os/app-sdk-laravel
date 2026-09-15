<?php

declare(strict_types=1);

namespace Nexia\Attachments;

/**
 * Authorized host file bytes exposed without leaking storage coordinates or
 * the host Eloquent model across the App boundary.
 */
final readonly class ReadableFile
{
    public function __construct(
        public string $publicId,
        public string $originalName,
        public string $mimeType,
        public ?string $checksum,
        public string $contents,
        public int|string|null $uploadedByKey = null,
    ) {}
}
