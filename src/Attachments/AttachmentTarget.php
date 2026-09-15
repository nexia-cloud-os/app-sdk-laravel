<?php

declare(strict_types=1);

namespace Nexia\Attachments;

/** Stable App resource identity to which a host file is attached. */
final readonly class AttachmentTarget
{
    public function __construct(
        public string $morphType,
        public int|string $key,
        public string $publicId,
    ) {}
}
