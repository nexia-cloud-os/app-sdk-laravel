<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use LogicException;
use Nexia\AppRuntime\Contracts\TenantInstallableAppManifest;

abstract class AbstractPackageAppManifest implements TenantInstallableAppManifest
{
    public function __construct(private readonly AppDefinition $appDefinition)
    {
        $manifestClass = static::class;

        if (! str_contains($manifestClass, '@anonymous')
            && $manifestClass !== $appDefinition->manifestClass) {
            throw new LogicException(
                "Package app manifest [{$manifestClass}] does not match metadata manifest [{$appDefinition->manifestClass}].",
            );
        }
    }

    final public function definition(): AppDefinition
    {
        return $this->appDefinition;
    }

    final public function id(): string
    {
        return $this->appDefinition->appKey;
    }

    final public function name(): string
    {
        return $this->appDefinition->appName;
    }

    public function contributionLocations(): array
    {
        return [];
    }

    public function pageElements(): array
    {
        return $this->pageElementsExtras();
    }

    /** @return array<string, string> */
    public function pageElementsExtras(): array
    {
        return [];
    }

    public function agentComponents(): array
    {
        return [];
    }

    public function tenantFilamentResources(): array
    {
        return [];
    }

    public function tenantMigrationPaths(): array
    {
        return [];
    }
}
