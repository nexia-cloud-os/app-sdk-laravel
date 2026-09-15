<?php

declare(strict_types=1);

namespace Nexia\SelfService;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/** Actor-bound query used to discover safe self-service work contexts. */
final readonly class SelfWorkContextQuery
{
    public function __construct(
        public Actor $actor,
        public ?LegalEntity $legalEntity,
    ) {}
}
