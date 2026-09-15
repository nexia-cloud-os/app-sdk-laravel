<?php

declare(strict_types=1);

namespace Nexia\Fixture;

use InvalidArgumentException;

/** App-published stable resource identity shared without exposing its model. */
final readonly class FixtureReference
{
    public function __construct(
        public string $resourceKey,
        public string $subjectKey,
        public string $id,
        public string $publicId,
        public ?string $morphType = null,
        public ?string $display = null,
    ) {
        if (trim($this->resourceKey) === ''
            || trim($this->subjectKey) === ''
            || trim($this->id) === ''
            || trim($this->publicId) === ''
        ) {
            throw new InvalidArgumentException('Fixture resource reference identity must be complete.');
        }
    }
}
