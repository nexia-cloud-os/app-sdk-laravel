<?php

declare(strict_types=1);

namespace Nexia\Reports\Contracts;

use Nexia\Reports\ReportDefinition;

interface ReportContribution
{
    /** @return list<ReportDefinition> */
    public static function reports(): array;
}
