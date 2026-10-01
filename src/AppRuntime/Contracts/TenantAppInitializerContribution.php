<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

use Nexia\AppRuntime\TenantAppInitializationContext;

/** Minimum operational defaults; idempotent, no demo data or external side effects. */
interface TenantAppInitializerContribution
{
    public const DEFAULT_VERSION = '1';

    public function appKey(): string;

    /** Runs in the owning App installation transaction. Preserve user-edited data. */
    public function initialize(TenantAppInitializationContext $context): void;
}
