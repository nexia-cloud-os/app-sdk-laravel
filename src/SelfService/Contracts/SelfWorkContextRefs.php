<?php

declare(strict_types=1);

namespace Nexia\SelfService\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\ResourceReference\Contracts\OpaqueResourceRefs;
use Nexia\ResourceReference\ResourceRef;

/** Resolves an opaque browser work-context reference for its bound actor. */
interface SelfWorkContextRefs extends OpaqueResourceRefs
{
    public function seal(Actor $actor, ResourceRef $resource): string;

    public function resolve(Actor $actor, string $reference): ?ResourceRef;
}
