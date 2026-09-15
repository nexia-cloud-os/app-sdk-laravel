<?php

declare(strict_types=1);

namespace Nexia\Templates;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

final readonly class TemplateApplicationContext
{
    public function __construct(
        public Actor $actor,
        public ?LegalEntity $legalEntity,
    ) {}
}
