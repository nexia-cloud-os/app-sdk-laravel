<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use Closure;
use InvalidArgumentException;

/** Independent reference reads in input order. */
final readonly class ResourceReferenceQueryBatch
{
    /** @internal */
    public function __construct(
        private object $owner,
        private array $requests,
        private Closure $run,
        private bool $sharedFailure = false,
    ) {
        if (func_num_args() > 4) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }
    }

    /** Group owner reads only when a shared failure is acceptable for every item. */
    public function allowSharedFailure(): self
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        return new self($this->owner, $this->requests, $this->run, true);
    }

    /** @return array{status:string,data:array,page:null,error:?array} */
    public function get(): array
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException('Unexpected reference query arguments.');
        }

        $requests = array_map(fn (mixed $query): mixed => $query instanceof ResourceReferenceQuery
            ? $query->snapshot($this->owner) : $query, $this->requests);

        return ($this->run)($requests, $this->sharedFailure);
    }
}
