<?php

declare(strict_types=1);

namespace Nexia\Identity\Query;

interface IdentityRead
{
    public function get(): array;
}
