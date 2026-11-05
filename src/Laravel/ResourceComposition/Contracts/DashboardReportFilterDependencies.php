<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/** Additional read permissions used by an opted-in dashboard report filter. */
interface DashboardReportFilterDependencies extends DashboardReportLegalEntityFilter
{
    /**
     * Same-App, active, legal-entity-scoped read permissions required to scope the
     * selected entities. The host authorizes each permission independently.
     *
     * @return list<non-empty-string>
     */
    public static function dashboardFilterPermissions(): array;
}
