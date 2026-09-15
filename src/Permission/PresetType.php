<?php

declare(strict_types=1);

namespace Nexia\Permission;

enum PresetType: string
{
    case Job = 'job';
    case Capability = 'capability';
    case Technical = 'technical';
}
