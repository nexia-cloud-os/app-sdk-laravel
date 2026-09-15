<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

enum ReferenceStatus: string
{
    case Available = 'available';
    case Absent = 'absent';
    case Disabled = 'disabled';
    case Failed = 'failed';
    case Stale = 'stale';
    case Unauthorized = 'unauthorized';
}
