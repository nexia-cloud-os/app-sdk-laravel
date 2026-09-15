<?php

declare(strict_types=1);

namespace Nexia\Permission\Contracts;

interface PermissionContribution
{
    /**
     * @return list<PermissionDefinition>
     */
    public static function catalogPermissionDefinitions(): array;
}
