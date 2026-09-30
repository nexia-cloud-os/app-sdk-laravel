<?php

declare(strict_types=1);

namespace Nexia\Email\Contracts;

interface EmailContributions
{
    /** Register a provider class, never a tenant-bound provider instance. */
    public function register(string $appKey, string $providerClass): void;
}
