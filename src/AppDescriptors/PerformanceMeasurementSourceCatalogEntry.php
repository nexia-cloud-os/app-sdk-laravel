<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/** An authorized source descriptor with host-verified App ownership. */
final readonly class PerformanceMeasurementSourceCatalogEntry
{
    public function __construct(
        public string $ownerAppKey,
        public ?string $resourceLabelKey,
        public PerformanceMeasurementSourceDescriptor $descriptor,
    ) {
        if (! ResourceRef::hasCanonicalIdentity($ownerAppKey, $descriptor->resourceKey)) {
            throw new InvalidArgumentException('Performance measurement source catalog ownership must match its Resource identity.');
        }
        if ($resourceLabelKey !== null
            && ($resourceLabelKey === '' || $resourceLabelKey !== trim($resourceLabelKey))) {
            throw new InvalidArgumentException('Performance measurement Resource label keys must be normalized when provided.');
        }
    }
}
