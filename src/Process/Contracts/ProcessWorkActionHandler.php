<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

use Nexia\Process\ProcessWorkActionInvocation;
use Nexia\Process\ProcessWorkActionResult;

/** App-owned implementation of one catalogued Process service action. */
interface ProcessWorkActionHandler
{
    public function handle(ProcessWorkActionInvocation $invocation): ProcessWorkActionResult;
}
