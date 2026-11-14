<?php

declare(strict_types=1);

namespace Nexia\Identity\Query;

use InvalidArgumentException;
/** Independent identity queries in input order. */
final readonly class IdentityQueryBatch
{
    /** @param list<IdentityRead> $queries */
    public function __construct(private array $queries)
    {
        if (func_num_args() > 1) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
    }

    public function get(): array
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected identity query arguments.');
        }
        if (! array_is_list($this->queries) || array_filter($this->queries, static fn ($query): bool => ! $query instanceof IdentityRead) !== []) {
            return IdentityQuery::error('INVALID_BATCH');
        }

        $results = [];
        for ($i = 0, $count = count($this->queries); $i < $count;) {
            $query = $this->queries[$i];
            $group = [$query];
            if ($query instanceof IdentityQuery) {
                while ($i + count($group) < $count && ($next = $this->queries[$i + count($group)]) instanceof IdentityQuery
                    && $query->batchCompatibleWith($next)) {
                    $group[] = $next;
                }
            }
            array_push($results, ...(count($group) > 1 ? $query->batchGet($group) : [$query->get()]));
            $i += count($group);
        }
        $success = count(array_filter($results, static fn (array $result): bool => $result['status'] === 'success'));

        return [
            'status' => $success === count($results) ? 'success' : ($success === 0 ? 'error' : 'partial'),
            'data' => $results,
            'page' => null,
            'error' => null,
        ];
    }
}
