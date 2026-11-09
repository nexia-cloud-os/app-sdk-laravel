<?php

declare(strict_types=1);

use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\PersonLoginAccounts;
use Nexia\Identity\PersonLoginInvitationAccessProfile;

require __DIR__.'/register-source-autoload.php';

$contract = new ReflectionClass(PersonLoginAccounts::class);
assert($contract->isInterface());
$method = $contract->getMethod('ensure');
assert((string) $method->getReturnType() === 'void');
assert($method->getNumberOfRequiredParameters() === 4);
$parameters = $method->getParameters();
assert(array_map(fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters)
    === ['actor', 'email', 'personPartyPublicId', 'legalEntityKey', 'accessProfile', 'operatingUnitPublicIds']);
assert((string) $parameters[0]->getType() === Actor::class);
assert($parameters[4]->getType()->allowsNull());
assert($parameters[4]->getType()->getName() === PersonLoginInvitationAccessProfile::class);
assert($parameters[4]->getDefaultValue() === null);
assert($parameters[5]->getDefaultValue() === []);

echo "Person login account contract checks passed.\n";
