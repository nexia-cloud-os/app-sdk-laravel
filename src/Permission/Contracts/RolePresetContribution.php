<?php

declare(strict_types=1);

namespace Nexia\Permission\Contracts;

interface RolePresetContribution
{
    /**
     * @return list<RolePreset>
     */
    public static function rolePresets(): array;
}
