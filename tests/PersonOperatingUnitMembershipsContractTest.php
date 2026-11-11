<?php

declare(strict_types=1);

use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\PersonOperatingUnitMemberships;

require __DIR__.'/register-source-autoload.php';

$contract = new ReflectionClass(PersonOperatingUnitMemberships::class);
assert($contract->isInterface());
$method = $contract->getMethod('move');
assert((string) $method->getReturnType() === 'array');
assert($method->getNumberOfRequiredParameters() === 7);
$parameters = $method->getParameters();
assert(array_map(fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters)
    === ['actor', 'personPartyPublicId', 'legalEntityKey', 'fromUnitPublicId', 'toUnitPublicId', 'makePrimary', 'sourceReference']);
assert((string) $parameters[0]->getType() === Actor::class);
assert($parameters[3]->getType()->allowsNull());
assert($parameters[4]->getType()->allowsNull());
assert(!$parameters[6]->getType()->allowsNull());
echo "Person organization membership contract checks passed.\n";
