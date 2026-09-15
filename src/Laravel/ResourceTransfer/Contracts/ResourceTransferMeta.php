<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceTransfer\Contracts;

use Illuminate\Http\Request;
use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;

interface ResourceTransferMeta
{
    /** @return array<string, mixed> */
    public function build(
        Request $request,
        ResourceTransferDefinition $definition,
        ?string $exportUrl = null,
        ?array $exportContext = null,
        ?string $legalEntityPublicId = null,
        ?string $operatingUnitPublicId = null,
    ): array;
}
