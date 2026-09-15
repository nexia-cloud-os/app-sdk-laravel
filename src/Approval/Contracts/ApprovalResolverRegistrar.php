<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\Contracts\ApprovalLineResolver;
use Nexia\Approval\Resolver\ApprovalResolverDescriptor;

interface ApprovalResolverRegistrar
{
    /** @param callable(): ApprovalLineResolver $factory */
    public function register(string $type, callable $factory, ApprovalResolverDescriptor $descriptor): void;

    public function registered(string $type): bool;
}
