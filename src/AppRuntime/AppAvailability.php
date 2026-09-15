<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

/**
 * Canonical availability of a target App from one consumer's viewpoint.
 *
 * This is App-level state — "can I talk to that App here, now?" — and is
 * distinct from the per-record ReferenceStatus and the per-request
 * HandoffResultState. Consumers surface these values as-is; renaming or
 * re-encoding them breaks the shared vocabulary.
 */
enum AppAvailability: string
{
    /** Installed, active, initialized, and exposing the needed contract. */
    case Operational = 'operational';

    /**
     * Not part of this tenant because it has never been installed. This maps
     * to ReferenceStatus::Absent and is the only state where a manual fallback
     * is legitimate.
     */
    case Unavailable = 'unavailable';

    /** Installed previously, but explicitly deactivated by an administrator. */
    case Disabled = 'disabled';

    /** The current actor lacks the permission this integration needs. */
    case Unauthorized = 'unauthorized';

    /** Installed but not usable because initialization or execution failed. */
    case Failed = 'failed';

    /** Installation state and the required contribution contract disagree. */
    case Stale = 'stale';

    public function permitsManualFallback(): bool
    {
        return $this === self::Unavailable;
    }
}
