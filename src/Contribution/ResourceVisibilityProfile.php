<?php

declare(strict_types=1);

namespace Nexia\Contribution;

enum ResourceVisibilityProfile: string
{
    case Standard = 'standard';
    case Custom = 'custom';
}
