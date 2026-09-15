<?php

declare(strict_types=1);

namespace Nexia\Testing;

final readonly class PublishedApprovalTemplate
{
    public function __construct(
        public string $key,
        public int $version,
    ) {}
}
