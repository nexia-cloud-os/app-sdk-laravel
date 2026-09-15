<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Execution\Contracts;

use Closure;
use Nexia\ResourceImport\Execution\ImportExecutionContext;

/** Host-owned authority restoration for one queued import operation. */
interface QueuedImportExecution
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  Closure(ImportExecutionContext): void  $execute
     * @param  Closure(): void|null  $unavailable
     * @return bool False when the tenant itself no longer exists.
     */
    public function run(
        int|string $tenantKey,
        int|string $actorKey,
        int|string|null $legalEntityKey,
        int|string|null $operatingUnitKey,
        string $path,
        array $payload,
        Closure $execute,
        ?Closure $unavailable = null,
    ): bool;
}
