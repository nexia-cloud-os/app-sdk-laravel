<?php

declare(strict_types=1);

namespace Nexia\Resources;

final readonly class ResourceListSort
{
    /** @param 'asc'|'desc' $direction */
    public function __construct(
        public string $key,
        public string $direction,
    ) {}
}
