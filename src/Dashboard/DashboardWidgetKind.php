<?php

declare(strict_types=1);

namespace Nexia\Dashboard;

enum DashboardWidgetKind: string
{
    case Overview = 'overview';
    case Metric = 'metric';
    case Table = 'table';
    case Timeline = 'timeline';
    case Chart = 'chart';
    case Command = 'command';
}
