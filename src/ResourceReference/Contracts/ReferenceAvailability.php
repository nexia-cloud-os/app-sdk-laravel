<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\ResourceReference\ReferenceStatus;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Host-evaluated lifecycle and authorization state for an App resource reference. */
interface ReferenceAvailability
{
    /** When supplied, the context actor and Legal Entity must match the leading authority coordinates. */
    public function status(
        Actor $actor,
        ?int $legalEntityId,
        string $resourceKey,
        ?ResourceReferenceResolutionContext $context = null,
    ): ReferenceStatus;

    public function ownerInstalled(string $resourceKey): bool;
}
