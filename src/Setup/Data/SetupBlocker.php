<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

/** A framework-neutral domain condition that prevents a setup task completing. */
final readonly class SetupBlocker
{
    public function __construct(
        public string $key,
        public string $descriptionKey,
    ) {
    }
}
