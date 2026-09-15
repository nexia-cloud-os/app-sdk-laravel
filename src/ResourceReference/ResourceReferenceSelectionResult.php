<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use InvalidArgumentException;

/** Availability plus an optional exact result for one declared field. */
final readonly class ResourceReferenceSelectionResult
{
    public function __construct(
        public ReferenceStatus $status,
        public ?ResolvedResourceReference $reference,
    ) {
        if ($reference !== null && $status !== ReferenceStatus::Available) {
            throw new InvalidArgumentException('Only available Resource Reference selections may contain a resolved reference.');
        }
    }
}
