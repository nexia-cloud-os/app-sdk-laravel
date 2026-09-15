<?php

declare(strict_types=1);

namespace Nexia\Laravel\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use Nexia\Resources\ResourceListQuery;
use Nexia\Resources\ResourceListQuerySchema;

/** Laravel adapter for an App controller's declared list-query schema. */
trait ParsesResourceListQuery
{
    protected function resourceListQuery(
        Request $request,
        object $resource,
        int $default = 25,
        int $minimum = 1,
        int $maximum = 100,
    ): ResourceListQuery {
        if (! method_exists($resource, 'resourceListQuerySchema')) {
            throw new LogicException(sprintf(
                'Resource [%s] does not expose a resource list query schema.',
                $resource::class,
            ));
        }

        $schema = $resource->resourceListQuerySchema($default, $minimum, $maximum);
        if (! $schema instanceof ResourceListQuerySchema) {
            throw new LogicException(sprintf(
                'Resource [%s] returned an invalid resource list query schema.',
                $resource::class,
            ));
        }

        return $this->parseResourceListQuery($request->query(), $schema);
    }

    protected function resourceListPageSize(
        Request $request,
        int $default = 25,
        int $max = 100,
        int $min = 1,
    ): int {
        return $this->parseResourceListQuery(
            ['per_page' => $request->query('per_page')],
            new ResourceListQuerySchema([], [], $default, $min, $max),
        )->perPage;
    }

    protected function resourceListSearch(Request $request): ?string
    {
        return $this->parseResourceListQuery(
            ['search' => $request->query('search')],
            new ResourceListQuerySchema([], []),
        )->search;
    }

    /** @param array<string, mixed> $input */
    private function parseResourceListQuery(array $input, ResourceListQuerySchema $schema): ResourceListQuery
    {
        try {
            return ResourceListQuery::fromInput($input, $schema);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['query' => [$exception->getMessage()]]);
        }
    }
}
