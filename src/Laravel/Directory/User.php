<?php

declare(strict_types=1);

namespace Nexia\Laravel\Directory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\ActorDirectory;
use Nexia\Organization\Contracts\LegalEntity as LegalEntityContext;

final class User extends DirectoryModel
{
    use SoftDeletes;

    protected $table = 'public.nexia_directory_query_users';

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_key');
    }

    public function scopeActiveForLegalEntity(Builder $query, LegalEntity|LegalEntityContext|int|string $entity): Builder
    {
        $key = match (true) {
            $entity instanceof LegalEntity => $entity->getKey(),
            $entity instanceof LegalEntityContext => $entity->key(),
            default => $entity,
        };
        $actors = app(ActorDirectory::class)->activeForLegalEntity($key);

        return $query->whereKey(array_map(static fn (Actor $actor) => $actor->key(), $actors));
    }

    public function actor(): ?Actor
    {
        return app(ActorDirectory::class)->findByKey($this->getKey(), $this->trashed());
    }
}
