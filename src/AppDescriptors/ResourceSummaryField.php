<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/** One app-owned, presentation-ready field in a resource summary. */
final readonly class ResourceSummaryField
{
    /**
     * @param  array<string, string>|null  $enumLabels  value => i18n key
     * @param  ResourceSummaryFieldRole|null  $role  Semantic placement for compact consumers
     */
    public function __construct(
        public string $key,
        public mixed $value,
        public string $type = 'string',
        public ?string $labelKey = null,
        public ?array $enumLabels = null,
        public ?ResourceSummaryFieldRole $role = null,
    ) {}
}
