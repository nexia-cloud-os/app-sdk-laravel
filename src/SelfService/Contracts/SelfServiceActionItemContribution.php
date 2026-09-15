<?php

declare(strict_types=1);

namespace Nexia\SelfService\Contracts;

interface SelfServiceActionItemContribution
{
    /** @return list<SelfServiceActionItem> */
    public function selfServiceActions(SelfServiceActionQuery $query): array;
}
