<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

use Nexia\AppRuntime\ShellResourceDescriptor;
use Nexia\Contribution\Contracts\ResourceCatalogContribution;

/** Publishes a resource's canonical or explicitly overridden shell routes. */
interface ShellResourceContribution extends ResourceCatalogContribution
{
    public static function shellResource(): ShellResourceDescriptor;
}
