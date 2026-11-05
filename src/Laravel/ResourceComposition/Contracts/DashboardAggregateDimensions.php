<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/**
 * App-owned dashboard-only measure dimensions; this does not grant field or row access.
 * Keys are numeric fields; values are public, selectable same-resource dimensions.
 * Each declared dimension also permits a single-value equality dashboard filter.
 *
 * @phpstan-type DashboardDimensions array<string, list<string>>
 */
interface DashboardAggregateDimensions
{
    /** @return array<string, list<string>> */
    public static function dashboardAggregateDimensions(): array;
}
