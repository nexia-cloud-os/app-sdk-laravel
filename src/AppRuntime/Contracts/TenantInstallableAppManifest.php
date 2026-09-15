<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

use Nexia\AppRuntime\AppDefinition;

interface TenantInstallableAppManifest extends AppManifest
{
    public function definition(): AppDefinition;
}
