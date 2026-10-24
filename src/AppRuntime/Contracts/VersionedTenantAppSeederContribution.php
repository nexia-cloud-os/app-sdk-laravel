<?php

declare(strict_types=1);

namespace Nexia\AppRuntime\Contracts;

/** Required Laravel seeders, ordered and checkpointed separately for each App database. */
interface VersionedTenantAppSeederContribution
{
    public function appKey(): string;

    /** @return array<positive-int, list<class-string<\Illuminate\Database\Seeder>>> */
    public function versions(): array;
}
