<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

interface AutomaticApprovalFactProviderRegistrar
{
    /** @param callable(): AutomaticApprovalFactProvider $factory */
    public function register(string $bindingKey, callable $factory): void;

    public function registered(string $bindingKey): bool;
}
