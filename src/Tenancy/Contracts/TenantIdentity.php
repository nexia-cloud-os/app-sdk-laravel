<?php

declare(strict_types=1);

namespace Nexia\Tenancy\Contracts;

/** Minimal tenant identity passed to work executed inside a Core-restored tenant context. */
interface TenantIdentity
{
    public function key(): int|string;
}
