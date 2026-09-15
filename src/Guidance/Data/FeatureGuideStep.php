<?php

declare(strict_types=1);

namespace Nexia\Guidance\Data;

/** One navigable, anchored step in a reusable feature guide. */
final readonly class FeatureGuideStep
{
    public function __construct(
        public string $key,
        public string $route,
        public string $anchor,
        public string $titleKey,
        public string $descriptionKey,
        public string $placement = 'bottom',
        public ?string $agentPromptKey = null,
    ) {}
}
