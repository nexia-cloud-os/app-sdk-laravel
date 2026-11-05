<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable;

/** Report-only filtering for a tenant-owned model with legal-entity applicability. */
interface ReportLegalEntityFilter
{
    /** @param list<int> $legalEntityIds */
    public function scopeReportLegalEntities(Builder $query, array $legalEntityIds, Authenticatable $actor): Builder;
}
