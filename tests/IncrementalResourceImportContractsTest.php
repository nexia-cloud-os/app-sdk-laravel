<?php

declare(strict_types=1);

use Nexia\ResourceImport\ImportBatchState;
use Nexia\ResourceImport\ImportPipelineStep;
use Nexia\ResourceImport\ImportPipelineWire;
use Nexia\ResourceImport\ImportStage;
use Nexia\ResourceImport\Contracts\PagedImportDecisionProvider;

require __DIR__.'/register-source-autoload.php';

assert(interface_exists(PagedImportDecisionProvider::class));
foreach (['import.review.page', 'import.review.stage', 'import.review.seal', 'import.review.operation'] as $method) {
    assert(in_array($method, ImportPipelineWire::METHODS, true));
}

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

$checkpointed = new ReflectionClass(\Nexia\ResourceImport\Contracts\WorkCheckpointedResourceImportPipeline::class);
assert($checkpointed->isSubclassOf(\Nexia\ResourceImport\Contracts\IncrementalResourceImportPipeline::class));
assert(array_map(fn (ReflectionParameter $p) => $p->getName(), $checkpointed->getMethod('applyNextForWork')->getParameters()) === ['batchId', 'legalEntity', 'actor', 'workId']);
