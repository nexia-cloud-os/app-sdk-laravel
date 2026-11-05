<?php

declare(strict_types=1);

namespace Nexia\Dashboard\Contracts;

interface DashboardDefaultContribution
{
    /** @return list<string> Ordered keys of this App's default dashboard widgets. */
    public static function dashboardDefaultWidgetKeys(): array;
}
