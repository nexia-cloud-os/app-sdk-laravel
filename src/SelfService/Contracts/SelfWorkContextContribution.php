<?php

declare(strict_types=1);

namespace Nexia\SelfService\Contracts;

/** App-owned dynamic provider of exact-self work contexts. */
interface SelfWorkContextContribution
{
    /** @return list<SelfWorkContextOption> */
    public function workContexts(SelfWorkContextQuery $query): array;
}
