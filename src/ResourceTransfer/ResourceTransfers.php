<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer;

use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;
use Nexia\ResourceTransfer\Contracts\ResourceTransferCatalog;

final class ResourceTransfers
{
    public function __construct(private readonly ResourceTransferCatalog $catalog) {}

    public function find(string $resourceKey): ?ResourceTransferDefinition
    {
        return $this->catalog->find($resourceKey);
    }
}
