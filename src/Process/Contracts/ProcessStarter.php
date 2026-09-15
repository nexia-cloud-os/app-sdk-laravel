<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

use Nexia\Process\BoundProcessStart;
use Nexia\Process\ProcessInstanceSnapshot;

/** App-neutral, binding-based Process start capability implemented by Core. */
interface ProcessStarter
{
    public function start(BoundProcessStart $request): ProcessInstanceSnapshot;
}
