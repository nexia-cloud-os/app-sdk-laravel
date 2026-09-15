<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

interface ProcessWorkActionRegistrar
{
    /** @param callable(): ProcessWorkActionHandler $factory */
    public function register(string $appKey, string $actionKey, callable $factory): void;

    public function registered(string $appKey, string $actionKey): bool;
}
