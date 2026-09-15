<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Approval\Contracts\ResourceSubjectActor;
use Nexia\Approval\Resolver\ResourceSubjectActorDescriptor;

/**
 * Host registry an App uses to declare who its records are *about*.
 *
 * Mirrors {@see ApprovalResolverRegistrar}: register at app boot, one entry per
 * resource key. Core keeps the registry and hands the resolved subject to every
 * approval-line resolver, so no resolver ever names the owning App.
 */
interface ResourceSubjectActorRegistrar
{
    /** @param callable(): ResourceSubjectActor $factory */
    public function register(callable $factory, ResourceSubjectActorDescriptor $descriptor): void;

    public function registered(string $resourceKey): bool;
}
