<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

interface AppRegistrar
{
    public function register(AppManifest $manifest): void;
}
