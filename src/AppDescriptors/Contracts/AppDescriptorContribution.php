<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors\Contracts;

use Nexia\AppDescriptors\AppDescriptorSet;

/** The one static discovery surface for App-published descriptors. */
interface AppDescriptorContribution
{
    public static function appDescriptors(): AppDescriptorSet;
}
