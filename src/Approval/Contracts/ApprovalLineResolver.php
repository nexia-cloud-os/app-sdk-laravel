<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

interface ApprovalLineResolver
{
    public function resolve(ResolverContext $context): ResolvedApprovalLine;
}
