<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

/** Host-selected installation identity, without a writable host model or credentials. */
final readonly class TenantAppInitializationContext
{
    public function __construct(
        public AppDefinition $definition,
        public string $tenantId,
        public string $initializationVersion,
    ) {}
}
