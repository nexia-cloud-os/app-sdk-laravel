<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/** Declares the existing dashboard source that supplies this provider's visibility dependencies. */
interface DashboardGenericVisibilitySource
{
    public static function dashboardGenericVisibilitySource(): string;
}
