<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Nexia\Laravel\ResourceTransfer\Concerns\ExportImportable;
use Nexia\ResourceTransfer\Contracts\ResourceTransferDefinition;
use Nexia\ResourceTransfer\ResourceTransferExportSourceDefinition;
use Nexia\ResourceTransfer\ResourceTransferModelDefinition;
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

$exportSchema = TransferSchema::make()->exportOnly('value', permission: 'fixture.export.execute',
    sensitive: true, requiresPurpose: true, requiresFreshAuthentication: true);
assert(TransferSchema::fromExportArray($exportSchema->toArray())->hash() === $exportSchema->hash());
foreach (['importable' => true, 'export_sensitive' => '1'] as $key => $value) {
    $invalid = $exportSchema->toArray(); $invalid['columns'][0][$key] = $value;
    try { TransferSchema::fromExportArray($invalid); throw new RuntimeException('Invalid export schema accepted.'); }
    catch (InvalidArgumentException|TypeError) {}
}
assert($labelledExportSource instanceof ResourceTransferDefinition);
assert(! $labelledExportSource->supportsImport());
assert(! str_contains(json_encode($labelledExportSource->toArray()), stdClass::class));

$modelDefinition = new ResourceTransferModelDefinition(
    'fixture.card',
    TransferSchema::make()->key('external_id')->field('name'),
    ResourceTransferDefinition::SCOPE_TENANT,
    ['fixture.card.read', 'fixture.card.create'],
    ['fixture.card.read'],
    'fixture.card.list.title',
);
$modelWire = $modelDefinition->toArray();
assert($modelDefinition instanceof ResourceTransferDefinition);
assert($modelDefinition->supportsImport());
assert($modelWire['execution'] === 'model');
assert($modelWire['permissions']['model'] === ['fixture.card.read', 'fixture.card.create']);
assert(TransferSchema::fromArray($modelWire['schema'])->hash() === $modelDefinition->schema->hash());
foreach ([['required' => 'yes'], ['unknown' => true]] as $change) {
    $invalid = $modelWire['schema'];
    $invalid['columns'][0] = [...$invalid['columns'][0], ...$change];
    try { TransferSchema::fromArray($invalid); throw new RuntimeException('Invalid model transfer schema accepted.'); }
    catch (InvalidArgumentException|TypeError) {}
}
try {
    new ResourceTransferModelDefinition(
        'fixture.card', TransferSchema::make()->field('name'), ResourceTransferDefinition::SCOPE_TENANT,
        ['fixture.card.read'], ['fixture.card.read'], 'fixture.card.list.title',
    );
    throw new RuntimeException('Importable model transfer accepted without create authority.');
} catch (InvalidArgumentException) {}
$exportOnlyModel = new ResourceTransferModelDefinition(
    'fixture.read_only_card', TransferSchema::make()->exportOnly('name'), ResourceTransferDefinition::SCOPE_TENANT,
    ['fixture.read_only_card.read'], ['fixture.read_only_card.read'], 'fixture.read_only_card.list.title',
);
assert(! $exportOnlyModel->supportsImport());
assert($exportOnlyModel->toArray()['import_formats'] === [] && $exportOnlyModel->toArray()['row_limits']['import'] === 0);
