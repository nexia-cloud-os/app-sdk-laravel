<?php

declare(strict_types=1);

namespace Nexia\AsyncWork\Contracts;

use Nexia\AsyncWork\AppWorkDefinition;

/** Declares bounded App work that the platform may execute after its own gates. */
interface AppWorkContribution
{
    /** @return list<AppWorkDefinition> */
    public static function appWork(): array;
}
