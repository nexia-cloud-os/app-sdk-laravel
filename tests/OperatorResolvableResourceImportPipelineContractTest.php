<?php

declare(strict_types=1);

use Nexia\ResourceImport\Contracts\OperatorResolvableResourceImportPipeline;

require __DIR__.'/register-source-autoload.php';

$contract = new ReflectionClass(OperatorResolvableResourceImportPipeline::class);
assert($contract->isInterface());
assert(array_map(fn (ReflectionParameter $p): string => $p->getName(), $contract->getMethod('inspectForResolution')->getParameters())
    === ['originalWorkId', 'operator']);
assert(array_map(fn (ReflectionParameter $p): string => $p->getName(), $contract->getMethod('closeForResolution')->getParameters())
    === ['originalWorkId', 'decisionFingerprint', 'evidenceFingerprint', 'expectedIdentity', 'operator']);
assert((string) $contract->getMethod('inspectForResolution')->getReturnType() === '?array');
assert((string) $contract->getMethod('closeForResolution')->getReturnType() === 'array');
echo "Operator-resolvable import pipeline contract passed.\n";
