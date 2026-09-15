<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

use Nexia\Organization\SiteView;

interface SiteDirectory
{
    public function findForLegalEntity(
        int|string $siteKey,
        int|string $legalEntityKey,
        ?string $asOf = null,
    ): ?SiteView;

    public function findByPublicIdForLegalEntity(
        string $publicId,
        int|string $legalEntityKey,
        ?string $asOf = null,
    ): ?SiteView;

    /** @return list<SiteView> */
    public function optionsForLegalEntity(
        int|string $legalEntityKey,
        ?string $asOf = null,
        string $search = '',
        int $limit = 50,
    ): array;
}
