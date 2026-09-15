<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\Resolver\ResolvedApprovalLine;
use Nexia\Approval\Resolver\ResolverContext;

interface ApprovalLineResolver
{
    public function resolve(ResolverContext $context): ResolvedApprovalLine;
}
