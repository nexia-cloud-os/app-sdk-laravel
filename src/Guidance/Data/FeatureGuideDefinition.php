<?php

declare(strict_types=1);

namespace Nexia\Guidance\Data;

/** Immutable, framework-neutral feature guide declaration. */
final readonly class FeatureGuideDefinition
{
    /**
     * @param  list<string>  $requiredAnyPermissions  At least one permission is required; an empty list makes the guide public to authenticated users.
     * @param  list<string>  $requiredAllPermissions  Every listed permission is required in addition to the any-of rule.
     * @param  list<FeatureGuideStep>  $steps
     */
    public function __construct(
        public string $key,
        public int $revision,
        public string $ownerKey,
        public int $priority,
        public string $titleKey,
        public string $descriptionKey,
        public string $icon,
        public array $requiredAnyPermissions,
        public array $requiredAllPermissions,
        public array $steps,
    ) {}
}
