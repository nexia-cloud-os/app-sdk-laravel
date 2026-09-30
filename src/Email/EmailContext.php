<?php

declare(strict_types=1);

namespace Nexia\Email;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Host-restored authority for previewing or preparing one business message. */
final readonly class EmailContext
{
    public function __construct(
        public string $tenantId,
        public Actor $actor,
        public LegalEntity $legalEntity,
        public ?ResourceRef $subject,
        public string $asOf,
    ) {}
}
