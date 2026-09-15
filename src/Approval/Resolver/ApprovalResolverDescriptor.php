<?php

declare(strict_types=1);

namespace Nexia\Approval\Resolver;

final readonly class ApprovalResolverDescriptor
{
    /** @param list<array<string, mixed>> $configSchema */
    public function __construct(
        public string $type,
        public ?string $ownerAppKey,
        public string $labelKey,
        public array $configSchema = [],
    ) {}
}
