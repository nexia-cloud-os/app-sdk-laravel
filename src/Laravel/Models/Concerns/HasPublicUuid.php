<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Gives an externally addressable model a UUID public identity while keeping
 * its internal primary key unchanged.
 *
 * @mixin Model
 */
trait HasPublicUuid
{
    use HasUuids;

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
