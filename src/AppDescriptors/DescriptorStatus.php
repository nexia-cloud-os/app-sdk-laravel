<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/** Lifecycle status of an App-contributed descriptor. */
enum DescriptorStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Removed = 'removed';
}
