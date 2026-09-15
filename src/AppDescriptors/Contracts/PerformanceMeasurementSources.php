<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors\Contracts;

use Nexia\AppDescriptors\PerformanceMeasurementSourceCatalogEntry;
use Nexia\Identity\Contracts\Actor;

interface PerformanceMeasurementSources
{
    /** @return list<PerformanceMeasurementSourceCatalogEntry> */
    public function authorizedFor(Actor $actor): array;
}
