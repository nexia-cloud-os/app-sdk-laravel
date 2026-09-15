<?php

declare(strict_types=1);

use Nexia\AppDescriptors\DescriptorStatus;

require dirname(__DIR__).'/vendor/autoload.php';

$values = array_map(
    static fn (DescriptorStatus $status): string => $status->value,
    DescriptorStatus::cases(),
);

if ($values !== ['active', 'deprecated', 'removed']) {
    throw new RuntimeException('DescriptorStatus values changed unexpectedly.');
}

fwrite(STDOUT, "DescriptorStatus contract is valid.\n");
