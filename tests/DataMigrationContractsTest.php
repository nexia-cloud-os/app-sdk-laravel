<?php

declare(strict_types=1);

use Nexia\DataMigration\Contracts\DataMigrationRunRecorder;
use Nexia\DataMigration\DataMigrationRunRecord;
use Nexia\DataMigration\DataMigrationRunResult;
use Nexia\DataMigration\DataMigrationRunStart;
use Nexia\DataMigration\DataMigrationRunStatus;

require dirname(__DIR__).'/vendor/autoload.php';

$start = new DataMigrationRunStart(
    stageKey: 'sample-owner.personnel',
    providerKey: 'ecount',
    targetKey: 'sample-owner',
    legalEntityPublicId: '019d0000-0000-7000-8000-000000000001',
    actorPublicId: '019d0000-0000-7000-8000-000000000002',
);
$result = new DataMigrationRunResult([
    'created' => 12,
    'replayed' => false,
    'batch_public_id' => '019d0000-0000-7000-8000-000000000003',
]);
$record = new DataMigrationRunRecord(
    publicId: '019d0000-0000-7000-8000-000000000004',
    stageKey: $start->stageKey,
    providerKey: $start->providerKey,
    targetKey: $start->targetKey,
    status: DataMigrationRunStatus::Completed,
    legalEntityPublicId: $start->legalEntityPublicId,
    result: $result,
    startedAt: new DateTimeImmutable('2026-08-31T01:00:00+00:00'),
    completedAt: new DateTimeImmutable('2026-08-31T01:01:00+00:00'),
);

if (! interface_exists(DataMigrationRunRecorder::class)
    || $record->toArray() !== [
        'public_id' => '019d0000-0000-7000-8000-000000000004',
        'stage_key' => 'sample-owner.personnel',
        'provider_key' => 'ecount',
        'target_key' => 'sample-owner',
        'status' => 'completed',
        'legal_entity_public_id' => '019d0000-0000-7000-8000-000000000001',
        'result' => [
            'created' => 12,
            'replayed' => false,
            'batch_public_id' => '019d0000-0000-7000-8000-000000000003',
        ],
        'started_at' => '2026-08-31T01:00:00+00:00',
        'completed_at' => '2026-08-31T01:01:00+00:00',
        'failed_at' => null,
    ]) {
    throw new RuntimeException('Data migration run wire contract changed unexpectedly.');
}

try {
    new DataMigrationRunResult(['raw_rows' => [['employee_number' => '1']]]);
    throw new RuntimeException('Data migration result must reject nested source data.');
} catch (InvalidArgumentException) {
    // Expected safety boundary.
}

fwrite(STDOUT, "Data migration contracts are valid.\n");
