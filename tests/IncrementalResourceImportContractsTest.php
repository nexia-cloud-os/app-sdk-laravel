<?php

declare(strict_types=1);

use Nexia\ResourceImport\ImportBatchState;
use Nexia\ResourceImport\ImportPipelineStep;
use Nexia\ResourceImport\ImportPipelineWire;
use Nexia\ResourceImport\ImportStage;

require __DIR__.'/register-source-autoload.php';

$pending = new ImportPipelineStep(null, 250, 1000);
assert($pending->toArray() === ['continuation' => ['processed' => 250, 'total' => 1000]]);
assert(! array_key_exists('applyEligible', $pending->toArray()));
$state = new ImportBatchState('batch', ImportStage::AwaitingApply, true, rowCount: 500, acceptedCount: 500);
assert((new ImportPipelineStep($state, 1000, 1000))->toArray() === ImportPipelineWire::state($state));
assert(ImportPipelineWire::restoreState((new ImportPipelineStep($state, 1000, 1000))->toArray())->applyEligible);
foreach ([[-1, 0], [0, -1], [2, 1]] as [$processed, $total]) {
    try {
        new ImportPipelineStep(null, $processed, $total);
        throw new LogicException('Invalid progress accepted.');
    } catch (InvalidArgumentException) {
    }
}
