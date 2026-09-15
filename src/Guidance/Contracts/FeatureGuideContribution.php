<?php

declare(strict_types=1);

namespace Nexia\Guidance\Contracts;

use Nexia\Guidance\Data\FeatureGuideDefinition;

/**
 * Declares reusable, role-aware feature guides owned by one installed App.
 *
 * Contributions contain only stable guide metadata. The host owns discovery,
 * permission filtering, route navigation, run state, and presentation.
 */
interface FeatureGuideContribution
{
    /** Canonical lower-kebab installed App key, or `core` for the host. */
    public function ownerKey(): string;

    /** @return list<FeatureGuideDefinition> */
    public function guides(): array;
}
