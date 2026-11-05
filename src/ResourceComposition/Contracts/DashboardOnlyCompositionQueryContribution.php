<?php

declare(strict_types=1);

namespace Nexia\ResourceComposition\Contracts;

/** Registers a provider solely for dashboard generic reads, not report composition. */
interface DashboardOnlyCompositionQueryContribution
{
    public static function resourceKey(): string;

    /** @return class-string<\Nexia\Laravel\ResourceComposition\Contracts\DashboardGenericCompositionQueryProvider> */
    public static function dashboardCompositionQueryProvider(): string;
}
