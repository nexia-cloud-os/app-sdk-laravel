<?php

declare(strict_types=1);

namespace Nexia\Fixture;

use InvalidArgumentException;

final readonly class FixtureLegalEntity
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
    ) {
        if ($this->id < 1 || trim($this->code) === '' || trim($this->name) === '') {
            throw new InvalidArgumentException('Fixture Legal Entity identity must be complete.');
        }
    }
}
