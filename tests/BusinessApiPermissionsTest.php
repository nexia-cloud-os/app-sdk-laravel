<?php

declare(strict_types=1);

require __DIR__.'/../src/AppRuntime/BusinessApiPermissions.php';

use Nexia\AppRuntime\BusinessApiPermissions;

assert((new BusinessApiPermissions(['sample.item.read']))->permissions === ['sample.item.read']);
foreach ([['sample.item.read', 'sample.item.read'], [''], ['bad permission'], [123], [1 => 'sample.item.read']] as $invalid) {
    try {
        new BusinessApiPermissions($invalid);
        throw new LogicException('Invalid permissions were accepted.');
    } catch (InvalidArgumentException) {
    }
}
