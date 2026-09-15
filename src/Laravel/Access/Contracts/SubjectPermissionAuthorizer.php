<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Nexia\Permission\SubjectPopulation;

interface SubjectPermissionAuthorizer
{
    public function allowsSubjectPopulation(
        Authenticatable $user,
        string $permissionKey,
        SubjectPopulation $population,
        ?int $legalEntityId = null,
        ?int $operatingUnitId = null,
    ): bool;
}
