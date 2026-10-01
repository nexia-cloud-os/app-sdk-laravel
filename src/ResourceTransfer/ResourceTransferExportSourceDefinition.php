<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer;

use InvalidArgumentException;
use Nexia\Laravel\ResourceTransfer\Contracts\ResourceTransferExportSource;

final readonly class ResourceTransferExportSourceDefinition implements Contracts\ResourceTransferDefinition
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

    public function supportsImport(): bool { return false; }

    public function usesContributedExportSource(): bool { return true; }

    /** Public metadata excludes the App-local PHP implementation. */
    public function toArray(): array
    {
        return [
            'resource_key' => $this->resourceKey, 'label_key' => $this->labelKey,
            'owner_app_key' => explode('.', $this->resourceKey, 2)[0],
            'schema' => $this->schema->toArray(), 'schema_hash' => $this->schema->hash(),
            'import_mode' => self::IMPORT_MODE_CREATE_ONLY, 'scope' => $this->scope,
            'formats' => array_values(array_intersect(['csv', 'xlsx'], $this->formats)),
            'export_formats' => array_values(array_unique($this->formats)), 'import_formats' => [],
            'row_limits' => ['export' => $this->rowLimit, 'import' => 0],
            'permissions' => ['system' => ['system.data.export', 'system.data.import'], 'model' => [],
                'export' => array_values(array_unique($this->permissionKeys))],
        ];
    }
}
