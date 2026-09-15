<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceTransfer\Concerns;

use Illuminate\Http\Request;
use Nexia\Laravel\ResourceTransfer\Contracts\ResourceTransferMeta;
use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;

trait BuildsResourceTransferMeta
{
    /** @return array<string, mixed> */
    protected function resourceTransferMeta(
        Request $request,
        ResourceTransferDefinition $definition,
        ?string $exportUrl = null,
        ?array $exportContext = null,
        ?string $legalEntityPublicId = null,
        ?string $operatingUnitPublicId = null,
    ): array {
        return resolve(ResourceTransferMeta::class)->build(
            $request,
            $definition,
            $exportUrl,
            $exportContext,
            $legalEntityPublicId,
            $operatingUnitPublicId,
        );
    }
}
