<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\ResourceImport\Plan\ImportPlan;

/**
 * A pipeline that can describe a frozen batch with the host's typed plan shapes.
 */
interface ImportPlanProvider
{
    public function planFor(string $batchId): ?ImportPlan;
}
