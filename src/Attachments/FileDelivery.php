<?php

declare(strict_types=1);

namespace Nexia\Attachments;

use InvalidArgumentException;

/** A server-observed delivery request; it never claims client receipt. */
final readonly class FileDelivery
{
    /** @param array<string, scalar|null> $context */
    public function __construct(
        public FileLifecycleSubject $subject,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public array $context = [],
    ) {
        if ($originalName === '' || $mimeType === '' || $sizeBytes < 0) {
            throw new InvalidArgumentException('File delivery metadata is invalid.');
        }
    }
}
