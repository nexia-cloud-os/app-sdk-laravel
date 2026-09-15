<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

use Nexia\AppRuntime\AppOperationalStatus;

interface InstalledApps
{
    public function isOperational(string $appKey): bool;

    public function availabilityStatus(string $appKey): AppOperationalStatus;
}
