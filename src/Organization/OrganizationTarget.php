<?php

declare(strict_types=1);

namespace Nexia\Organization;

use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;

/** One host-authorized Legal Entity target, optionally narrowed to one Operating Unit. */
final readonly class OrganizationTarget
{
    public function __construct(
        public LegalEntity $legalEntity,
        public ?OperatingUnit $operatingUnit = null,
    ) {}
}
