<?php

declare(strict_types=1);

namespace Nexia\Attachments;

/** Host file metadata returned without exposing the host Eloquent model. */
final readonly class StoredFile
{
    public function __construct(
        public int|string $key,
        public string $publicId,
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public string $checksum,
        public int|string|null $uploadedByKey = null,
        public int|string|null $legalEntityKey = null,
        public ?string $scanStatus = null,
        public ?string $uploadPurpose = null,
        public ?string $scanEngine = null,
        public ?string $scanEngineVersion = null,
        public ?string $scanSignatureVersion = null,
    ) {}
}
