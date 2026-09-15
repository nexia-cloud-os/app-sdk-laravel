<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

/** Host-reported availability state for an App contribution. */
enum AppOperationalStatus: string
{
    case Absent = 'absent';
    case Disabled = 'disabled';
    case Failed = 'failed';
    case Stale = 'stale';
    case Available = 'available';
}
