<?php

declare(strict_types=1);

namespace Nexia\Permission\Contracts;

use Nexia\Permission\RolePreset;

interface RolePresetContribution
{
    /**
     * @return list<RolePreset>
     */
    public static function rolePresets(): array;
}
