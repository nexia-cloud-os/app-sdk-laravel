<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Concerns;

use Nexia\AppRuntime\ShellResourceDescriptor;

/** Default canonical shell-resource route declaration. */
trait HasShellResource
{
    public static function shellResource(): ShellResourceDescriptor
    {
        return new ShellResourceDescriptor(shapes: ['list', 'record']);
    }
}
