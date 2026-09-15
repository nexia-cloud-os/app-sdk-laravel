<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

/** One named, framework-neutral piece of completion evidence from an evaluator. */
final readonly class SetupCriterion
{
    public function __construct(
        public string $key,
        public string $labelKey,
        public bool $satisfied,
        public bool $required = true,
    ) {
    }
}
