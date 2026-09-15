<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ResourceAuthorization
{
    public function scopeVisible(Builder $query, string $resourceKey, ?int $legalEntityId = null): Builder;

    /**
     * Applies standard direct Legal Entity-owner visibility for a permitted
     * multi-Legal-Entity list. Custom and Operating Unit visibility stay with
     * their owning resource query.
     *
     * @param list<int> $legalEntityIds
     */
    public function scopeVisibleForLegalEntities(Builder $query, string $resourceKey, array $legalEntityIds): Builder;

    public function recordMatches(Model $record, string $resourceKey, ?int $legalEntityId = null): bool;

    /** @return array<string, int> */
    public function creationAttributes(string $resourceKey, ?int $legalEntityId = null): array;
}
