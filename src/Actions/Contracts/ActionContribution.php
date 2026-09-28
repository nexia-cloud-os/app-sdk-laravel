<?php

declare(strict_types=1);

namespace Nexia\Actions\Contracts;

use Nexia\Actions\ActionDefinition;

/** App-wide actions; Resource actions live on the Resource Module descriptor. */
interface ActionContribution
{
    /** @return list<ActionDefinition> */
    public static function actions(): array;
}
