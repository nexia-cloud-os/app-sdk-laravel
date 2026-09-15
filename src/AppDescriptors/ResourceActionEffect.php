<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/** Whether a Resource action observes state or changes it. */
enum ResourceActionEffect: string
{
    case Read = 'read';
    case Mutate = 'mutate';
}
