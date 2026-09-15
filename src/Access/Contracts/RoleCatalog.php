<?php

declare(strict_types=1);

namespace Nexia\Access\Contracts;

interface RoleCatalog
{
    public function exists(string $publicId): bool;
}
