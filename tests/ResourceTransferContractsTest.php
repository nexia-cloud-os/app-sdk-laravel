<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Nexia\Laravel\ResourceTransfer\Concerns\ExportImportable;
use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;
use Nexia\ResourceTransfer\ResourceTransferExportSourceDefinition;
use Nexia\ResourceTransfer\TransferSchema;

require dirname(__DIR__).'/vendor/autoload.php';

$schema = TransferSchema::make()
    ->key('public_id', required: true)
    ->field('label')
    ->exportOnly('secret_mask', permission: 'sample.card.view_sensitive')
    ->importOnly('source_key');

if (! $schema->hasColumns()
    || count($schema->columns()) !== 4
    || $schema->columns()[0]['unique_import_key'] !== true
    || $schema->columns()[2]['export_permission'] !== 'sample.card.view_sensitive'
    || $schema->hash() !== hash('sha256', json_encode($schema->toArray(), JSON_THROW_ON_ERROR))
) {
    throw new RuntimeException('TransferSchema contract changed unexpectedly.');
}

try {
    TransferSchema::make()->key('public_id')->key('external_id');
    throw new RuntimeException('TransferSchema must reject multiple import keys.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

if (ResourceTransferDefinition::IMPORT_MODE_CREATE_ONLY !== 'createOnly'
    || ResourceTransferDefinition::SCOPE_TENANT !== 'tenant'
    || ResourceTransferDefinition::SCOPE_LEGAL_ENTITY !== 'legal_entity'
    || ResourceTransferDefinition::SCOPE_OPERATING_UNIT !== 'operating_unit'
) {
    throw new RuntimeException('ResourceTransferDefinition constants changed unexpectedly.');
}

$legacyExportSource = new ResourceTransferExportSourceDefinition(
    'fixture.legacy_export',
    stdClass::class,
    $schema,
    ResourceTransferDefinition::SCOPE_TENANT,
    ['fixture.legacy_export.export'],
);
$labelledExportSource = new ResourceTransferExportSourceDefinition(
    resourceKey: 'fixture.labelled_export',
    sourceClass: stdClass::class,
    schema: $schema,
    scope: ResourceTransferDefinition::SCOPE_TENANT,
    permissionKeys: ['fixture.labelled_export.export'],
    labelKey: 'fixture.labelled_export.label',
);

if ($legacyExportSource->labelKey !== null
    || $labelledExportSource->labelKey !== 'fixture.labelled_export.label'
) {
    throw new RuntimeException('ResourceTransferExportSourceDefinition label compatibility changed unexpectedly.');
}

try {
    new ResourceTransferExportSourceDefinition(
        resourceKey: 'fixture.blank_label_export',
        sourceClass: stdClass::class,
        schema: $schema,
        scope: ResourceTransferDefinition::SCOPE_TENANT,
        permissionKeys: ['fixture.blank_label_export.export'],
        labelKey: ' ',
    );
    throw new RuntimeException('ResourceTransferExportSourceDefinition must reject a blank label key.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

$transferModel = new class extends Model
{
    use ExportImportable;
};

if ($transferModel::TRANSFER_SCOPE_TENANT !== ResourceTransferDefinition::SCOPE_TENANT
    || $transferModel::TRANSFER_SCOPE_LEGAL_ENTITY !== ResourceTransferDefinition::SCOPE_LEGAL_ENTITY
    || $transferModel::transferScope() !== ResourceTransferDefinition::SCOPE_TENANT
    || $transferModel::transferColumns($schema) !== $schema
    || $transferModel::transferExportPermissionKeys() !== []
) {
    throw new RuntimeException('ExportImportable defaults changed unexpectedly.');
}

fwrite(STDOUT, "Resource Transfer contracts are valid.\n");
