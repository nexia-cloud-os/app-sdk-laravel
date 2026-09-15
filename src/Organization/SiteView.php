<?php

declare(strict_types=1);

namespace Nexia\Organization;

final readonly class SiteView
{
    /** @param array<string, mixed>|null $address */
    public function __construct(
        public int|string $key,
        public string $publicId,
        public string $code,
        public string $label,
        public string $type,
        public ?string $timezone,
        public ?array $address,
    ) {}
}
