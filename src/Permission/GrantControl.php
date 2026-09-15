<?php

declare(strict_types=1);

namespace Nexia\Permission;

enum GrantControl: string
{
    case Standard = 'standard';
    case Protected = 'protected';
}
