<?php

declare(strict_types=1);

namespace Nexia\Tenancy\Contracts;

/** Read-only settings resolved from the current tenant context. */
interface TenantSettings
{
    public function businessTimezone(): string;
}
