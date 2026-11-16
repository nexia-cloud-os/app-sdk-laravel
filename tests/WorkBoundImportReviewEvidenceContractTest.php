<?php

declare(strict_types=1);

use Nexia\ResourceImport\Contracts\WorkBoundImportReviewEvidence;

require __DIR__.'/register-source-autoload.php';

$contract = new ReflectionClass(WorkBoundImportReviewEvidence::class);
$submit = $contract->getMethod('submit');
assert($contract->isInterface() && (string) $submit->getReturnType() === 'void');
assert($submit->getNumberOfRequiredParameters() === 1 && $submit->getParameters()[0]->getType()->allowsNull());
echo "Work-bound import review evidence contract passed.\n";
