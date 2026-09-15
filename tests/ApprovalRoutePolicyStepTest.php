<?php

declare(strict_types=1);

use Nexia\Approval\Domain\ApprovalRoutePolicyStep;

require dirname(__DIR__).'/vendor/autoload.php';

$expected = [
    'resolver_type' => 'fixed_user',
    'config' => ['user_ids' => [10, 20], 'fallback' => false],
];

$step = ApprovalRoutePolicyStep::fromArray($expected);

if ($step->resolverType !== 'fixed_user' || $step->toArray() !== $expected) {
    throw new RuntimeException('Approval route policy step round-trip changed unexpectedly.');
}

$normalized = ApprovalRoutePolicyStep::fromArray([
    'resolver_type' => 123,
    'config' => 'invalid',
]);

if ($normalized->toArray() !== ['resolver_type' => '123', 'config' => []]) {
    throw new RuntimeException('Approval route policy step normalization changed unexpectedly.');
}

fwrite(STDOUT, "Approval route policy step contract passed.\n");
