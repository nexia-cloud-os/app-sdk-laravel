<?php

declare(strict_types=1);

namespace Nexia\Laravel\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as SqlBuilder;
use Nexia\Organization\Contracts\SiteEligibility;

final class Site extends DirectoryModel
{
    protected $table = 'public.nexia_directory_query_sites';

    public function scopeForLegalEntity(Builder $query, int|string $legalEntityKey, ?string $asOf = null): Builder
    {
        if (getenv('NEXIA_LOCAL_DATABASE') === '1') {
            return $query->whereKey(app(SiteEligibility::class)->keysForLegalEntity($legalEntityKey, $asOf));
        }

        $date = $asOf ?? now()->toDateString();
        $roles = $query->getQuery()->getConnection()->table('public.nexia_directory_site_roles')
            ->where('legal_entity_id', $legalEntityKey)->select('site_id');
        $this->effectiveAt($roles, 'effective_from', 'effective_until', $date);
        $this->effectiveAt($query->getQuery(), $this->qualifyColumn('effective_from'), $this->qualifyColumn('effective_until'), $date);

        return $query->where($this->qualifyColumn('status'), 'active')->whereIn($this->getQualifiedKeyName(), $roles);
    }

    private function effectiveAt(SqlBuilder $query, string $from, string $until, string $date): void
    {
        $query->where(fn (SqlBuilder $window) => $window->whereNull($from)->orWhereDate($from, '<=', $date))
            ->where(fn (SqlBuilder $window) => $window->whereNull($until)->orWhereDate($until, '>', $date));
    }
}
