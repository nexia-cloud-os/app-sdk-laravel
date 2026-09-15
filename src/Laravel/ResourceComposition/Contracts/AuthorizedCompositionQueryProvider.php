<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

use Nexia\Laravel\ResourceComposition\AuthorizedCompositionQuery;
use Nexia\Laravel\ResourceComposition\CompositionQueryContext;

/** App-owned row scope for a Resource whose standard host scope is insufficient. */
interface AuthorizedCompositionQueryProvider
{
    public function resourceKey(): string;

    /** Returns an App-authorized query and the aliases Core may consume. */
    public function query(CompositionQueryContext $context): AuthorizedCompositionQuery;

}
