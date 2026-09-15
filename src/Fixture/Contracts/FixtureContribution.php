<?php

declare(strict_types=1);

namespace Nexia\Fixture\Contracts;

use Nexia\Fixture\FixtureContext;

/** App-owned producer of disposable records for an explicit fixture profile. */
interface FixtureContribution
{
    public function appKey(): string;

    /** @return list<string> */
    public function fixtureKeys(): array;

    public function seed(FixtureContext $context): void;
}
