<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

/** Functional currency of a legal entity, when configured. */
interface HasFunctionalCurrency
{
    public function functionalCurrencyCode(): ?string;

    public function functionalCurrencyMinorUnit(): ?int;
}
