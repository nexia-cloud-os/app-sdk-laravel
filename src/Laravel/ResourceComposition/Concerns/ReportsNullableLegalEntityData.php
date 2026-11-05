<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/** Use only when null means tenant-wide and the direct legal entity is the complete applicability rule. */
trait ReportsNullableLegalEntityData
{
    public function scopeReportLegalEntities(Builder $query, array $legalEntityIds, Authenticatable $actor): Builder
    {
        if ($legalEntityIds === []) return $query->whereRaw('1 = 0');
        $column = $query->getModel()->qualifyColumn('legal_entity_id');
        return $query->where(static fn (Builder $scope) => $scope->whereNull($column)->orWhereIn($column, $legalEntityIds));
    }
}
