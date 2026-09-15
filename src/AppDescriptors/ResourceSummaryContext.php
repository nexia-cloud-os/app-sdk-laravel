<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/** Core-restored context an owning App uses to authorize and redact a resource summary. */
final readonly class ResourceSummaryContext
{
    public function __construct(
        public ?LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceVisibility $visibility,
    ) {}
}
