<?php

declare(strict_types=1);

namespace Nexia\Settings\Contracts;

use Nexia\Settings\SettingDefinition;

interface SettingsContribution
{
    /** @return list<SettingDefinition> Definitions only; do not read environment or stored values. */
    public static function settings(): array;
}
