<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

use Nexia\Organization\LegalEntityProfile;

/** Optional enrichment of a LegalEntity obtained through authorized organization lookup. */
interface HasLegalEntityProfile
{
    public function legalProfile(): LegalEntityProfile;
}
