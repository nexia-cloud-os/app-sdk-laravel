<?php

declare(strict_types=1);

namespace Nexia\Laravel\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexia\Organization\Contracts\LegalEntity as LegalEntityContext;
use Nexia\Organization\Contracts\OrganizationDirectory;

final class LegalEntity extends DirectoryModel
{
    protected $table = 'public.nexia_directory_legal_entities';

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_public_id', 'public_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('active'), true);
    }

    public function hierarchyRevision(): ?int
    {
        return app(OrganizationDirectory::class)->legalEntityHierarchyRevisionByKey($this->getKey());
    }

    public function context(): ?LegalEntityContext
    {
        return app(OrganizationDirectory::class)->findLegalEntityByKey($this->getKey());
    }
}
