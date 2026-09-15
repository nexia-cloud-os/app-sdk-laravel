<?php

declare(strict_types=1);

namespace Nexia\Permission\Contracts;

use Nexia\Permission\PermissionDefinition;

interface PermissionContribution
{
    /**
     * @return list<PermissionDefinition>
     */
    public static function catalogPermissionDefinitions(): array;
}
