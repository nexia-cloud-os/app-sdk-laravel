<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

interface VersionedTenantAppInitializerContribution extends TenantAppInitializerContribution
{
    public function version(): string;
}
