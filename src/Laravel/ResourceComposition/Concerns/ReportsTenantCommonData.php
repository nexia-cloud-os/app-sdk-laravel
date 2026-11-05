<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/** Use only for tenant definitions whose records do not depend on legal-entity selection. */
trait ReportsTenantCommonData
{
    public function scopeReportLegalEntities(Builder $query, array $legalEntityIds, Authenticatable $actor): Builder
    {
        return $legalEntityIds === [] ? $query->whereRaw('1 = 0') : $query;
    }
}
