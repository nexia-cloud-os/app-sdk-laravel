<?php

declare(strict_types=1);

namespace Nexia\SelfService;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

final readonly class SelfServiceActionQuery
{
    public function __construct(
        public Actor $actor,
        public ?LegalEntity $legalEntity,
        public ?ResourceRef $workContext,
    ) {}
}
