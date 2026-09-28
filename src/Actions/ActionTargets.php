<?php

declare(strict_types=1);

namespace Nexia\Actions;

enum ActionTargets: string
{
    case None = 'none';
    case One = 'one';
    case Many = 'many';
}
