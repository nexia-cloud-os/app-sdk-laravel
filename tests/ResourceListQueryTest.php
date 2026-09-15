<?php

declare(strict_types=1);

use Nexia\Resources\ResourceListQuery;
use Nexia\Resources\ResourceListQuerySchema;

require dirname(__DIR__).'/vendor/autoload.php';

$schema = new ResourceListQuerySchema(
    filterKeys: ['status', 'owner'],
    sortKeys: ['created_at', 'name'],
    defaultPerPage: 25,
    maximumPerPage: 100,
);

$query = ResourceListQuery::fromInput([
    'search' => '  machine  ',
    'sort' => '-created_at',
    'filter' => ['status' => ['active', '', 'active'], 'owner' => '  me  '],
    'page' => '2',
    'per_page' => '50',
], $schema);

if ($query->search !== 'machine'
    || $query->sort?->key !== 'created_at'
    || $query->sort?->direction !== 'desc'
    || $query->filters !== ['status' => ['active'], 'owner' => 'me']
    || $query->page !== 2
    || $query->perPage !== 50) {
    throw new RuntimeException('Resource list query parsing changed unexpectedly.');
}

foreach ([
    ['sort' => 'unknown'],
    ['filter' => ['unknown' => 'value']],
    ['per_page' => '101'],
    ['page' => '0'],
] as $input) {
    try {
        ResourceListQuery::fromInput($input, $schema);
        throw new RuntimeException('Invalid resource list input was accepted.');
    } catch (InvalidArgumentException) {
    }
}

fwrite(STDOUT, "Resource list query contracts passed.\n");
