<?php

declare(strict_types=1);

namespace Nexia\Templates\Contracts;

use Nexia\Templates\CopyableTemplate;
use Nexia\Templates\TemplateApplicationContext;
use Nexia\Templates\TemplateApplicationResult;

interface CopyableTemplateContribution
{
    /** @return list<CopyableTemplate> */
    public function templates(): array;

    public function apply(CopyableTemplate $template, TemplateApplicationContext $context): TemplateApplicationResult;
}
