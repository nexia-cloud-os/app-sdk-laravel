<?php

declare(strict_types=1);

namespace Nexia\Laravel\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexia\Organization\Contracts\OperatingUnit as OperatingUnitContext;
use Nexia\Organization\Contracts\OrganizationDirectory;

final class OperatingUnit extends DirectoryModel
{
    protected $table = 'public.nexia_directory_operating_units';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_key');
    }

    public function scopeAffiliatedWithLegalEntity(Builder $query, int|string $legalEntityKey): Builder
    {
        return $query->whereKey(app(OrganizationDirectory::class)->operatingUnitKeysForLegalEntity($legalEntityKey));
    }

    public function scopeForLegalEntity(Builder $query, int|string $legalEntityKey, ?string $asOf = null): Builder
    {
        $units = app(OrganizationDirectory::class)->effectiveOperatingUnitsForLegalEntity($legalEntityKey, $asOf);

        return $query->whereKey(array_map(static fn (OperatingUnitContext $unit) => $unit->key(), $units));
    }

    public function context(): ?OperatingUnitContext
    {
        return app(OrganizationDirectory::class)->findOperatingUnitByKey($this->getKey());
    }
}
