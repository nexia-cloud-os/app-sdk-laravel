<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/** Presentation-ready summary produced by the App that owns a resource. */
final readonly class ResourceSummary
{
    /** @param list<ResourceSummaryField> $fields */
    public function __construct(
        public string $display,
        public ?string $route = null,
        public array $fields = [],
        public ?string $icon = null,
    ) {}
}
