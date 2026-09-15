<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * App-contributed widget for a platform-owned slot.
 *
 * Slot keys belong to a closed namespace owned by the platform. Apps fill a
 * documented slot rather than inventing one inline. Slot props are a
 * versioned public API: additive changes are minor and type-breaking changes
 * require a deprecation cycle.
 */
final class SlotWidgetDescriptor implements AppDescriptor
{
    /**
     * @param  string|list<string>|null  $permission
     */
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly string $slot,
        public readonly string $component,
        public readonly int $slotApiVersion,
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly string|array|null $permission = null,
        public readonly int $sort = 100,
        public readonly ?string $labelKey = null,
        public readonly ?string $familyKey = null,
    ) {
        if ($familyKey !== null && trim($familyKey) === '') {
            throw new \InvalidArgumentException(
                "SlotWidgetDescriptor [{$key}] family_key must be a non-empty string.",
            );
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
