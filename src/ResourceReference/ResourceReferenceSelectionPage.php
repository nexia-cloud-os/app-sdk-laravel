<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use InvalidArgumentException;

/** Availability plus the owner-authorized option page for one declared field. */
final readonly class ResourceReferenceSelectionPage
{
    public function __construct(
        public ReferenceStatus $status,
        public ResourceReferencePage $page,
    ) {
        if ($status !== ReferenceStatus::Available && $page->total !== 0) {
            throw new InvalidArgumentException('Unavailable Resource Reference selections must return an empty page.');
        }
    }
}
