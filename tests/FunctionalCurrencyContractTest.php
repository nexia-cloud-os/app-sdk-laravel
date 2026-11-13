<?php

declare(strict_types=1);

use Nexia\Organization\Contracts\HasFunctionalCurrency;

require __DIR__.'/register-source-autoload.php';

$contract = new ReflectionClass(HasFunctionalCurrency::class);
assert($contract->isInterface());
assert((string) $contract->getMethod('functionalCurrencyCode')->getReturnType() === '?string');
assert((string) $contract->getMethod('functionalCurrencyMinorUnit')->getReturnType() === '?int');
