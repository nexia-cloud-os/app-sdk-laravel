<?php

declare(strict_types=1);

namespace Nexia\SelfService\Contracts;

use Nexia\SelfService\SelfServiceActionItem;
use Nexia\SelfService\SelfServiceActionQuery;

interface SelfServiceActionItemContribution
{
    /** @return list<SelfServiceActionItem> */
    public function selfServiceActions(SelfServiceActionQuery $query): array;
}
