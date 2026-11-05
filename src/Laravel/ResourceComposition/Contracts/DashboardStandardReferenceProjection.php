<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/** Dashboard-only projection for a standard Legal Entity Resource with masked local references. */
interface DashboardStandardReferenceProjection extends DashboardGenericListProjectionProvider
{
    /** @return list<non-empty-string> Same-App Legal Entity read permissions, checked per selected entity. */
    public static function dashboardReferencePermissions(): array;
}
