<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

interface ProcessUserTaskSubmissionRegistrar
{
    /** @param callable(): ProcessUserTaskSubmissionHandler $factory */
    public function register(string $appKey, string $actionKey, callable $factory): void;

    public function registered(string $appKey, string $actionKey): bool;
}
