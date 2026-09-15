<?php

declare(strict_types=1);

require dirname(__DIR__).'/scripts/generate-public-api-snapshot.php';

$additions = publicApiAdditions(
    ['symbols' => [['name' => 'Nexia\\Existing\\Contract']]],
    ['symbols' => [
        ['name' => 'Nexia\\Existing\\Contract'],
        ['name' => 'Nexia\\Existing\\NewContract'],
        ['name' => 'Nexia\\NewFamily\\Contract'],
    ]],
);

if ($additions !== [
    'namespaces' => ['NewFamily'],
    'symbols' => ['Nexia\\Existing\\NewContract', 'Nexia\\NewFamily\\Contract'],
]) {
    throw new RuntimeException('Public API additions review changed unexpectedly.');
}

if (publicApiReview($additions) !== "New top-level namespaces: NewFamily\nNew public symbols: Nexia\\Existing\\NewContract, Nexia\\NewFamily\\Contract") {
    throw new RuntimeException('Public API additions report changed unexpectedly.');
}

echo "Public API snapshot review passed.\n";
