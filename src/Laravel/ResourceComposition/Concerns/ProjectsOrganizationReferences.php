<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Nexia\Laravel\ResourceComposition\OrganizationReferenceProjection;

/** Standard trait for projecting organization entity references and display labels. */
trait ProjectsOrganizationReferences
{
    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    protected function projectLegalEntity(Builder $query, string $sourceColumn, array $targets): Builder
    {
        return OrganizationReferenceProjection::legalEntity($query, $sourceColumn, $targets);
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    protected function projectOperatingUnit(Builder $query, string $sourceColumn, array $targets): Builder
    {
        return OrganizationReferenceProjection::operatingUnit($query, $sourceColumn, $targets);
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    protected function projectLegalEntityLabel(Builder $query, string $sourceColumn, array $targets): Builder
    {
        return OrganizationReferenceProjection::legalEntityLabel($query, $sourceColumn, $targets);
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    protected function projectOperatingUnitLabel(Builder $query, string $sourceColumn, array $targets): Builder
    {
        return OrganizationReferenceProjection::operatingUnitLabel($query, $sourceColumn, $targets);
    }
}
