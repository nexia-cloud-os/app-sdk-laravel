<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer;

use InvalidArgumentException;
use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;

/** Public, class-free declaration for a model transfer executed by its owning App runtime. */
final readonly class ResourceTransferModelDefinition implements ResourceTransferDefinition
{
    /** @param list<string> $modelPermissionKeys @param list<string> $exportPermissionKeys */
    public function __construct(
        public string $resourceKey,
        public TransferSchema $schema,
        public string $scope,
        public array $modelPermissionKeys,
        public array $exportPermissionKeys,
        public ?string $labelKey = null,
        public array $rowLimits = ['export' => 10000, 'import' => 1000],
    ) {
        $hasImport = false;
        foreach ($schema->columns() as $column) {
            $hasImport = $hasImport || $column['importable'];
        }
        if ($resourceKey === '' || $schema->hasColumns() === false || $modelPermissionKeys === []
            || $exportPermissionKeys === [] || ! in_array($scope, [self::SCOPE_TENANT, self::SCOPE_LEGAL_ENTITY, self::SCOPE_OPERATING_UNIT], true)
            || ! is_int($rowLimits['export'] ?? null) || ! is_int($rowLimits['import'] ?? null)
            || $rowLimits['export'] < 1 || $rowLimits['import'] < 1
            || ! in_array($resourceKey.'.read', $modelPermissionKeys, true)
            || ! in_array($resourceKey.'.read', $exportPermissionKeys, true)
            || ($hasImport && ! in_array($resourceKey.'.create', $modelPermissionKeys, true))) {
            throw new InvalidArgumentException('Invalid model Resource Transfer definition.');
        }
    }

    public function supportsImport(): bool
    {
        foreach ($this->schema->columns() as $column) {
            if ($column['importable']) {
                return true;
            }
        }

        return false;
    }

    public function usesContributedExportSource(): bool { return false; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $supportsImport = $this->supportsImport();

        return [
            'execution' => 'model',
            'resource_key' => $this->resourceKey,
            'label_key' => $this->labelKey,
            'owner_app_key' => explode('.', $this->resourceKey, 2)[0],
            'schema' => $this->schema->toArray(),
            'schema_hash' => $this->schema->hash(),
            'import_mode' => self::IMPORT_MODE_CREATE_ONLY,
            'scope' => $this->scope,
            'formats' => ['csv', 'xlsx'],
            'export_formats' => ['csv', 'xlsx', 'json', 'txt'],
            'import_formats' => $supportsImport ? ['csv', 'xlsx', 'txt'] : [],
            'row_limits' => ['export' => $this->rowLimits['export'], 'import' => $supportsImport ? $this->rowLimits['import'] : 0],
            'permissions' => [
                'system' => ['system.data.export', 'system.data.import'],
                'model' => array_values(array_unique($this->modelPermissionKeys)),
                'export' => array_values(array_unique($this->exportPermissionKeys)),
            ],
        ];
    }
}
