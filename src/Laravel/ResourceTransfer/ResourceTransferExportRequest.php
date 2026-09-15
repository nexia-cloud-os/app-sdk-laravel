<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceTransfer;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;

final readonly class ResourceTransferExportRequest
{
    /** @param list<string> $columns */
    public function __construct(
        public ResourceTransferDefinition $definition,
        public array $columns,
        public ?Authenticatable $actor,
        public Request $httpRequest,
        public ?int $legalEntityId,
        public ?string $legalEntityPublicId,
        public ?int $operatingUnitId,
        public ?string $operatingUnitPublicId,
        public int $rowLimit,
    ) {}
}
