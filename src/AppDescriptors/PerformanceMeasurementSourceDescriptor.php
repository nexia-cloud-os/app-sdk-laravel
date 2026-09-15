<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/**
 * App-neutral discovery metadata for numeric facts owned by one Resource.
 *
 * Results always identify their subject Party and source Legal Entity. Values,
 * providers, scoring, weighting, and consumer models stay outside this descriptor.
 */
final readonly class PerformanceMeasurementSourceDescriptor implements AppDescriptor
{
    /** @param non-empty-list<PerformanceMeasurementDefinition> $measurements */
    public function __construct(
        public string $resourceKey,
        public string $version,
        public array $measurements,
        public DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        $separator = strpos($resourceKey, '.');
        $appKey = $separator === false ? '' : substr($resourceKey, 0, $separator);
        if (! ResourceRef::hasCanonicalIdentity($appKey, $resourceKey)) {
            throw new InvalidArgumentException('Performance measurement sources require a canonical Resource key.');
        }
        if ($version === '' || $version !== trim($version) || mb_strlen($version) > 64) {
            throw new InvalidArgumentException('Performance measurement source versions must be normalized and at most 64 characters.');
        }
        if (! array_is_list($measurements) || $measurements === []) {
            throw new InvalidArgumentException('Performance measurement sources require at least one measurement.');
        }

        $keys = [];
        foreach ($measurements as $measurement) {
            if (! $measurement instanceof PerformanceMeasurementDefinition || isset($keys[$measurement->key])) {
                throw new InvalidArgumentException('Performance measurement sources require uniquely keyed typed measurements.');
            }
            if (! str_starts_with($measurement->labelKey, $appKey.'.')) {
                throw new InvalidArgumentException('Performance measurement labels must be owned by the source App.');
            }
            $keys[$measurement->key] = true;
        }
    }

    public function descriptorKey(): string
    {
        return $this->resourceKey;
    }
}
