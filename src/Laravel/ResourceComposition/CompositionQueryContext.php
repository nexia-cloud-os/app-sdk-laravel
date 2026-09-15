<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/** Restored host context for an App-owned composition visibility query. */
final readonly class CompositionQueryContext
{
    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $organizationTargets */
    public function __construct(
        public string $resourceKey,
        public Authenticatable $actor,
        public Request $request,
        public array $organizationTargets,
    ) {}
}
