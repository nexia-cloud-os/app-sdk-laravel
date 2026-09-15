<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

enum PerformanceMeasurementValueType: string
{
    case Integer = 'integer';
    case Decimal = 'decimal';
}
