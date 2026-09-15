<?php

declare(strict_types=1);

namespace Nexia\Contribution\Contracts;

use Nexia\Contribution\ResourceAuthorizationContract;

interface ResourceAuthorizationContribution extends ResourceCatalogContribution
{
    public static function resourceAuthorization(): ResourceAuthorizationContract;
}
