<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Nexia\Laravel\Filters\Contracts\Filter;
use Nexia\Laravel\Filters\Types\Callback;
use Nexia\Laravel\Filters\Types\Exact;

require dirname(__DIR__).'/vendor/autoload.php';

$callback = new Callback(static function (Builder $query, mixed $value): void {});
$exact = new Exact('status');

if (! $callback instanceof Filter || ! $exact instanceof Filter) {
    throw new RuntimeException('SDK filter implementations must satisfy the public Filter contract.');
}

fwrite(STDOUT, "Filter contracts are valid.\n");
