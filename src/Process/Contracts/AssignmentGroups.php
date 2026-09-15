<?php

declare(strict_types=1);

namespace Nexia\Process\Contracts;

use Nexia\Identity\Contracts\Actor;

interface AssignmentGroups
{
    public function includes(Actor $actor, string $groupCode, ?int $legalEntityId = null): bool;
}
