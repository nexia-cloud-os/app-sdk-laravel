<?php

declare(strict_types=1);

namespace Nexia\Laravel\Resources\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lets a resource with a derived public id apply a predicate to the query
 * owning that canonical id. Consumers never need to know its table or joins.
 */
interface ResourceListCanonicalIdentity
{
    /** @param Closure(Builder): void $constraint */
    public function applyResourceListCanonicalIdConstraint(Builder $query, Closure $constraint): Builder;
}
