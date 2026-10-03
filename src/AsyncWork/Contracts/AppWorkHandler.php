<?php

declare(strict_types=1);

namespace Nexia\AsyncWork\Contracts;

use Nexia\AsyncWork\AppWorkInvocation;

/** Executes one already-authorized App work item in its selected tenant scope. */
interface AppWorkHandler
{
    public function handle(AppWorkInvocation $invocation): void;
}
