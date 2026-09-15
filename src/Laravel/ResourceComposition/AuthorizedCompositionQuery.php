<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition;

use Illuminate\Database\Eloquent\Builder;

/** App-owned authorized query plus the only semantic aliases Core may use. */
final readonly class AuthorizedCompositionQuery
{
    /**
     * Fields may declare required_dimensions: list<string> of same-source field
     * keys. Value aggregates require these exact unbucketed dimensions or a
     * single-value equality constraint. Count and distinct_count are exempt.
     * Core enforces this for direct and derived measures, including comparisons.
     *
     * @param  array<string,array<string,mixed>>  $fields
     */
    public function __construct(
        public Builder $query,
        public array $fields,
        public string $grainAlias = 'public_id',
    ) {}
}
