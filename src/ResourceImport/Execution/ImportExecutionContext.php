<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Execution;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;

/** Restored authority and scope for one queued import operation. */
final readonly class ImportExecutionContext
{
    public function __construct(
        public Actor $actor,
        public ?LegalEntity $legalEntity,
        public ?OperatingUnit $operatingUnit,
    ) {}
}
