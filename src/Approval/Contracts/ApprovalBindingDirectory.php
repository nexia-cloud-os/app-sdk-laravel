<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\ApprovalBindingAvailability;
use Nexia\Organization\Contracts\LegalEntity;

/** Read-only view of whether a contributed business form is live for a tenant. */
interface ApprovalBindingDirectory
{
    /**
     * Resolves the published template a Legal Entity would file this binding
     * with, preferring a Legal Entity-scoped template over a tenant-wide one.
     */
    public function availabilityFor(string $bindingKey, ?LegalEntity $legalEntity): ApprovalBindingAvailability;
}
