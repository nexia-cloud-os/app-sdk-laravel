<?php

declare(strict_types=1);

namespace Nexia\Contribution\Contracts;

interface ResourceAuthorizationContribution extends ResourceCatalogContribution
{
    public static function resourceAuthorization(): ResourceAuthorizationContract;
}
