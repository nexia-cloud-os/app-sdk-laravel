<?php

declare(strict_types=1);

namespace Nexia\Testing;

/** Opaque host-owned record projection for App integration tests. */
final readonly class HostTestRecord
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        public int|string $key,
        public ?string $publicId,
        public array $attributes,
    ) {}

    public function value(string $attribute): mixed
    {
        return $this->attributes[$attribute] ?? null;
    }
}
