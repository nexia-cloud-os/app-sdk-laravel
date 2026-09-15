<?php

declare(strict_types=1);

namespace Nexia\Attachments;

final readonly class AttachmentSummary
{
    public function __construct(
        public string $publicId,
        public string $attachableType,
        public ?string $attachablePublicId,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public int $downloadCount,
        public ?string $lastDownloadedAt,
        public ?string $createdAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'attachable_type' => $this->attachableType,
            'attachable_public_id' => $this->attachablePublicId,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'download_count' => $this->downloadCount,
            'last_downloaded_at' => $this->lastDownloadedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
