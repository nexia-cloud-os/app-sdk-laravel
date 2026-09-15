<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** App-neutral person option returned by the host Party directory. */
final readonly class PersonDirectoryEntry
{
    public function __construct(
        public int|string $key,
        public string $publicId,
        public string $displayLabel,
        public ?string $contactEmail,
    ) {}
}
