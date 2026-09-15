<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Nexia\Laravel\Http\Concerns\ParsesResourceListQuery;
use Nexia\Resources\ResourceListQuerySchema;

require dirname(__DIR__).'/vendor/autoload.php';

$parser = new class
{
    use ParsesResourceListQuery;

    public function query(Request $request): array
    {
        $resource = new class
        {
            public function resourceListQuerySchema(int $default, int $minimum, int $maximum): ResourceListQuerySchema
            {
                return new ResourceListQuerySchema(['status'], ['created_at'], $default, $minimum, $maximum);
            }
        };

        $query = $this->resourceListQuery($request, $resource, default: 25, maximum: 100);

        return [$query->search, $query->sort?->key, $query->sort?->direction, $query->filters, $query->perPage];
    }
};

if ($parser->query(Request::create('/', 'GET', [
    'search' => '  worker  ',
    'sort' => '-created_at',
    'filter' => ['status' => 'active'],
    'per_page' => '40',
])) !== ['worker', 'created_at', 'desc', ['status' => 'active'], 40]
) {
    throw new RuntimeException('Laravel resource list query adapter changed unexpectedly.');
}

fwrite(STDOUT, "Laravel HTTP adapter contracts passed.\n");
