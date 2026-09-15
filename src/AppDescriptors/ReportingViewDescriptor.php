<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;

final class ReportingViewDescriptor implements AppDescriptor
{
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly string $viewName,
        public readonly array $columns,
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
    ) {}

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
