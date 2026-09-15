<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

use Nexia\Process\ProcessUserTaskSubmissionInvocation;
use Nexia\Process\ProcessUserTaskSubmissionResult;

/** App-owned domain write performed while an authorized Process UserTask completes. */
interface ProcessUserTaskSubmissionHandler
{
    public function handle(ProcessUserTaskSubmissionInvocation $invocation): ProcessUserTaskSubmissionResult;
}
