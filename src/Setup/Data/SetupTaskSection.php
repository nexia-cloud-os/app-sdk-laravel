<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

/**
 * An optional section within the owning App's Setup tasks.
 *
 * App Family and App placement are derived by the host from the contribution
 * owner and AppDefinition. Contributors declare this only when their own task
 * list benefits from an additional grouping layer.
 */
final readonly class SetupTaskSection
{
    public function __construct(
        public string $key,
        public string $titleKey,
        public ?string $descriptionKey,
        public int $priority,
    ) {
    }
}
