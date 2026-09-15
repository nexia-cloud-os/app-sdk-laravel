<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer;

use InvalidArgumentException;
use Nexia\Laravel\ResourceTransfer\Contracts\ResourceTransferExportSource;

final readonly class ResourceTransferExportSourceDefinition
{
    /**
     * @param  class-string<ResourceTransferExportSource>  $sourceClass
     * @param  list<string>  $permissionKeys
     * @param  list<string>  $formats
     */
    public function __construct(
        public string $resourceKey,
        public string $sourceClass,
        public TransferSchema $schema,
        public string $scope,
        public array $permissionKeys,
        public array $formats = ['csv', 'xlsx', 'json', 'txt'],
        public int $rowLimit = 10000,
        public ?string $labelKey = null,
    ) {
        if ($this->labelKey !== null && trim($this->labelKey) === '') {
            throw new InvalidArgumentException('A Resource Transfer export source label key cannot be empty.');
        }
    }
}
