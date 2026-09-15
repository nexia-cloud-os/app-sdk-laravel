<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Core-provided mapping from an Eloquent model to its stable resource key.
 *
 * App packages depend on this contract instead of a Core resource registry.
 */
interface SearchResourceIdentityResolver
{
    public function tenantKey(): int|string|null;

    /** @param class-string<Model> $modelClass */
    public function resourceKeyForModel(string $modelClass): ?string;
}
