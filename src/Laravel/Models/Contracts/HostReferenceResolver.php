<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\Party;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;

/** Resolves scalar host foreign keys for Laravel adapter models without Core model class exposure. */
interface HostReferenceResolver
{
    public function actorByKey(int|string|null $key): ?Actor;

    public function partyByKey(int|string|null $key): ?Party;

    public function legalEntityByKey(int|string|null $key): ?LegalEntity;

    public function operatingUnitByKey(int|string|null $key): ?OperatingUnit;
}
