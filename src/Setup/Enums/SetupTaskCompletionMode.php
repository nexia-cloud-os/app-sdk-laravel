<?php

declare(strict_types=1);

namespace Nexia\Setup\Enums;

/** Whether the host re-evaluates completion or retains it for this revision. */
enum SetupTaskCompletionMode: string
{
    case Live = 'live';
    case Latched = 'latched';
}
