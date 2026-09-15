<?php

declare(strict_types=1);

namespace Nexia\Testing\Contracts;

use Nexia\AppDescriptors\ResourceDescriptor;
use Nexia\AppRuntime\Contracts\AppManifest;
use Nexia\ResourceReference\Contracts\ResourceReferenceResolutionContribution;

/** Integration-test contribution registration and inspection surface. */
interface ContributionTestHost
{
    /** @return list<array<string, mixed>> */
    public function permissionDefinitions(): array;

    /** @return list<array<string, mixed>> */
    public function rolePresets(): array;

    /** @return array<string, mixed> */
    public function pageElements(): array;

    /** @return array<string, mixed> */
    public function agentManifest(): array;

    public function reset(): void;

    public function registerLocation(string $namespace, string $path): void;

    public function registerApp(AppManifest $manifest): void;

    public function appRegistered(string $appKey): bool;

    public function registerReference(ResourceReferenceResolutionContribution $reference): void;

    public function registerResourceDescriptor(ResourceDescriptor $descriptor): void;

    public function resourceOwnerAppKey(string $resourceKey): ?string;

    public function hasReferenceProvider(string $resourceKey): bool;

    /** @return array<string, array<string, mixed>> */
    public function publicResourceFacetCatalog(): array;

    /** @param array<string, mixed> $trigger @return array<string, mixed> */
    public function normalizeProcessTrigger(array $trigger): array;
}
