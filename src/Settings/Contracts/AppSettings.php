<?php

declare(strict_types=1);

namespace Nexia\Settings\Contracts;

use Nexia\Settings\SettingValue;

/** Backend-only reads in the host's authenticated App, Tenant and environment context. */
interface AppSettings
{
    /** Scopes are explicit: app and tenant never fall back to one another. */
    public function get(string $scope, string $key): SettingValue;
}
