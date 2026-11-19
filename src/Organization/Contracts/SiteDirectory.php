<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

use Nexia\Organization\SiteView;

interface SiteDirectory
{
    /**
     * For display, use `Site::forLegalEntity($legalEntityKey, $asOf)->find($siteKey)`.
     * Keep the host method for a `SiteView` or address.
     *
     * @see \Nexia\Laravel\Directory\Site
     */
    public function findForLegalEntity(
        int|string $siteKey,
        int|string $legalEntityKey,
        ?string $asOf = null,
    ): ?SiteView;

    /**
     * For display, use `Site::forLegalEntity($legalEntityKey, $asOf)->where('public_id', $publicId)->first()`.
     * Keep this method for a SiteView, address, or write validation.
     *
     * @see \Nexia\Laravel\Directory\Site
     */
    public function findByPublicIdForLegalEntity(
        string $publicId,
        int|string $legalEntityKey,
        ?string $asOf = null,
    ): ?SiteView;

    /**
     * For basic site options, use Site::forLegalEntity($legalEntityKey, $asOf) and add label or code filtering.
     * Use this method when the options need SiteView objects or site addresses.
     *
     * @return list<SiteView>
     * @see \Nexia\Laravel\Directory\Site
     */
    public function optionsForLegalEntity(
        int|string $legalEntityKey,
        ?string $asOf = null,
        string $search = '',
        int $limit = 50,
    ): array;
}
