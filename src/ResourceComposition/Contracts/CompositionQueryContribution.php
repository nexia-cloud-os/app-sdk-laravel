<?php

declare(strict_types=1);

namespace Nexia\ResourceComposition\Contracts;

use Nexia\Contribution\Contracts\ResourceCatalogContribution;

/** Binds one Resource descriptor to its App-owned composition visibility query. */
interface CompositionQueryContribution extends ResourceCatalogContribution
{
    /** @return class-string<AuthorizedCompositionQueryProvider> */
    public static function compositionQueryProvider(): string;
}
