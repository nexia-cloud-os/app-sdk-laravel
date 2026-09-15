<?php

declare(strict_types=1);

namespace Nexia\Dashboard\Contracts;

use Nexia\Dashboard\DashboardQuerySource;

/** App-owned Dashboard read sources discovered from contribution locations. */
interface DashboardQuerySourceContribution
{
    /** @return list<DashboardQuerySource> */
    public static function dashboardQuerySources(): array;
}
