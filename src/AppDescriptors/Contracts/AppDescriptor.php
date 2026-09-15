<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors\Contracts;

/** A typed, static descriptor published by an App Package. */
interface AppDescriptor
{
    /** Stable key within this descriptor's concrete type. */
    public function descriptorKey(): string;
}
