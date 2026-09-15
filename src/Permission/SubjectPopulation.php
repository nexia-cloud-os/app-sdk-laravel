<?php

declare(strict_types=1);

namespace Nexia\Permission;

enum SubjectPopulation: string
{
    /** Internal wildcard used by protected and recovery grants. */
    public const ALL = 'all';

    case Self = 'self';
    case DirectReports = 'direct_reports';
    case LegalEntity = 'legal_entity';
    case OperatingUnit = 'operating_unit';
}
