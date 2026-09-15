<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

interface AppManifest
{
    public function id(): string;

    public function name(): string;

    /** @return list<array{namespace: string, directory: string}> */
    public function contributionLocations(): array;

    /** @return array<string, string> */
    public function pageElements(): array;

    /** @return list<string> */
    public function agentComponents(): array;

    /** @return list<class-string> */
    public function tenantFilamentResources(): array;

    /** @return list<string> */
    public function tenantMigrationPaths(): array;
}
