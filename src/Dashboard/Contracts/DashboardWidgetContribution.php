<?php

declare(strict_types=1);

namespace Nexia\Dashboard\Contracts;

use Nexia\Contribution\Contracts\ResourceCatalogContribution;

interface DashboardWidgetContribution extends ResourceCatalogContribution
{
    /** @return list<DashboardWidget> */
    public static function dashboardWidgets(): array;
}
