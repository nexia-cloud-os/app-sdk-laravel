<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

enum PerformanceMeasurementTimeSemantics: string
{
    case OccurredAt = 'occurred_at';
    case Period = 'period';
}
