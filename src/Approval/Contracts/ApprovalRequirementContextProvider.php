<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\ApprovalRequirementContext;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/** Implement on the existing binding contributor; no second registration is needed. */
interface ApprovalRequirementContextProvider
{
    /** @return list<ApprovalRequirementContext> Conditions the actor may configure in this scope. */
    public function approvalRequirementContexts(string $bindingKey, ?LegalEntity $legalEntity, Actor $actor): array;
}
