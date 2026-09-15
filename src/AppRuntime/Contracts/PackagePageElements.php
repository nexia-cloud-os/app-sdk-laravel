<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

interface PackagePageElements
{
    /** @return array<string, string> */
    public function resolve(AppManifest $manifest): array;
}
