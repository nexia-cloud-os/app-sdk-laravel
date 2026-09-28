<?php

declare(strict_types=1);

namespace Nexia\Dashboard\Contracts;

use Nexia\Dashboard\DashboardWidget;

interface DashboardWidgetContribution
{
    /** @return list<DashboardWidget> */
    public static function dashboardWidgets(): array;
}
