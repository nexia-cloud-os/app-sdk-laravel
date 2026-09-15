<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer\Contracts;

use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;

interface ResourceTransferCatalog
{
    public function find(string $resourceKey): ?ResourceTransferDefinition;
}
