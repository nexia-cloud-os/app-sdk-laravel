<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;

/** Actor and organization context restored by Core for owner-authorized reference resolution. */
final readonly class ResourceReferenceResolutionContext
{
    public function __construct(
        public ?LegalEntity $legalEntity,
        public Actor $actor,
        public DateTimeImmutable $asOf,
        public string $purpose,
        public ?OperatingUnit $operatingUnit = null,
        public bool $includeProtectedValues = false,
        public ?DateTimeImmutable $effectiveThrough = null,
    ) {
        if (trim($purpose) === '' || mb_strlen($purpose) > 160) {
            throw new InvalidArgumentException('Resource Reference resolution requires a bounded purpose.');
        }

        if ($effectiveThrough !== null && $effectiveThrough < $asOf) {
            throw new InvalidArgumentException('Resource Reference effective-through must not precede as-of.');
        }
    }
}
