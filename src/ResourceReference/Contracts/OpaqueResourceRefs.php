<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\ResourceReference\ResourceRef;

/** Actor- and tenant-bound browser handle for a canonical Resource Reference. */
interface OpaqueResourceRefs
{
    public function seal(Actor $actor, ResourceRef $resource): string;

    public function resolve(Actor $actor, string $reference): ?ResourceRef;
}
