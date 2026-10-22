<?php

declare(strict_types=1);

namespace Nexia\Integrations\Contracts;

use Nexia\Integrations\ExternalApiDefinition;

interface ExternalApiContribution
{
    /** @return list<ExternalApiDefinition> Definitions only; never include credentials. */
    public static function externalApis(): array;
}
