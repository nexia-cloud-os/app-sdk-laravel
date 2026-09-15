<?php

declare(strict_types=1);

namespace Nexia\Templates;

final readonly class TemplateApplicationResult
{
    /**
     * @param  list<array{type: string, id: int|string, key?: string, version?: int|string}>  $resourceRefs
     */
    public function __construct(public array $resourceRefs) {}
}
