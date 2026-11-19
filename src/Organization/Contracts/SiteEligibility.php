<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

/** Resolves current eligible Site keys before filtering a developer-local snapshot. */
interface SiteEligibility
{
    /** @return list<int> */
    public function keysForLegalEntity(int|string $legalEntityKey, ?string $asOf = null): array;
}
