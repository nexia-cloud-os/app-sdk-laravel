<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/**
 * Declares an App-local aggregate parent for dashboard-only child reads.
 * The host must validate the declaration against the accepted catalog and
 * check the current actor's parent read and parent record policy for each row.
 * This declaration alone grants no access.
 */
interface DashboardGenericParentResource
{
    /** Resource key of the parent, whose read permission is required. */
    public static function dashboardParentResource(): string;

    /** Child column containing the parent's public UUID. */
    public static function dashboardParentForeignKey(): string;
}
