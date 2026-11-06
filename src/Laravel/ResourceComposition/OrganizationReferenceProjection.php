<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nexia\Organization\Contracts\OrganizationDirectory;
use Throwable;

/** Projects already-authorized organization targets and directory references as public UUIDs and display labels without a Core-table join. */
final class OrganizationReferenceProjection
{
    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    public static function legalEntity(Builder $query, string $sourceColumn, array $targets): Builder
    {
        return self::legalEntityExpression($query, $sourceColumn, $targets);
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    public static function legalEntityExpression(Builder $query, string $sourceExpression, array $targets): Builder
    {
        $keys = self::keys($targets, 'legal_entity_id');
        try {
            if (Schema::hasTable('legal_entities')) {
                $allKeys = DB::table('legal_entities')
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
                if ($allKeys !== []) {
                    $keys = array_values(array_unique(array_merge($keys, $allKeys)));
                }
            }
        } catch (Throwable) {
        }

        return self::project(
            $query,
            $sourceExpression,
            'legal_entity_public_id',
            app(OrganizationDirectory::class)->legalEntityPublicIdsByKeys($keys),
        );
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    public static function operatingUnit(Builder $query, string $sourceColumn, array $targets): Builder
    {
        $keys = self::keys($targets, 'operating_unit_id');
        try {
            if (Schema::hasTable('operating_units')) {
                $allUnitKeys = DB::table('operating_units')
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
                if ($allUnitKeys !== []) {
                    $keys = array_values(array_unique(array_merge($keys, $allUnitKeys)));
                }
            }
        } catch (Throwable) {
        }

        return self::project(
            $query,
            $sourceColumn,
            'operating_unit_public_id',
            app(OrganizationDirectory::class)->operatingUnitPublicIdsByKeys($keys),
        );
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    public static function legalEntityLabel(Builder $query, string $sourceColumn, array $targets): Builder
    {
        $keys = self::keys($targets, 'legal_entity_id');
        try {
            if (Schema::hasTable('legal_entities')) {
                $rows = DB::table('legal_entities')
                    ->whereNull('deleted_at')
                    ->select(['id', 'display_label'])
                    ->get();
                $labels = [];
                foreach ($rows as $row) {
                    $labels[(int) $row->id] = (string) $row->display_label;
                }
                if ($labels !== []) {
                    return self::projectLabels($query, $sourceColumn, 'legal_entity_label', $labels);
                }
            }
        } catch (Throwable) {
        }

        $directory = app(OrganizationDirectory::class);
        $labels = [];
        foreach ($directory->findLegalEntitiesByKeys($keys) as $entity) {
            $labels[(int) $entity->key()] = $entity->displayLabel();
        }

        return self::projectLabels($query, $sourceColumn, 'legal_entity_label', $labels);
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets */
    public static function operatingUnitLabel(Builder $query, string $sourceColumn, array $targets): Builder
    {
        $keys = self::keys($targets, 'operating_unit_id');
        try {
            if (Schema::hasTable('operating_units')) {
                $rows = DB::table('operating_units')
                    ->whereNull('deleted_at')
                    ->select(['id', 'display_label'])
                    ->get();
                $labels = [];
                foreach ($rows as $row) {
                    $labels[(int) $row->id] = (string) $row->display_label;
                }
                if ($labels !== []) {
                    return self::projectLabels($query, $sourceColumn, 'operating_unit_label', $labels);
                }
            }
        } catch (Throwable) {
        }

        $directory = app(OrganizationDirectory::class);
        $labels = [];
        foreach ($directory->findOperatingUnitsByKeys($keys) as $unit) {
            $labels[(int) $unit->key()] = $unit->displayLabel();
        }

        return self::projectLabels($query, $sourceColumn, 'operating_unit_label', $labels);
    }

    /** @param list<array{legal_entity_id:int,operating_unit_id:int|null}> $targets @return list<int> */
    private static function keys(array $targets, string $key): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (array $target): mixed => $target[$key] ?? null, $targets),
            static fn (mixed $value): bool => is_int($value) && $value > 0,
        )));
    }

    /** @param array<int|string,string> $publicIds */
    public static function project(Builder $query, string $sourceColumn, string $alias, array $publicIds): Builder
    {
        $pairs = [];
        foreach ($publicIds as $key => $publicId) {
            if ((is_int($key) || ctype_digit((string) $key)) && (int) $key > 0 && is_string($publicId) && $publicId !== '') {
                $pairs[(int) $key] = $publicId;
            }
        }
        if ($pairs === []) {
            return $query->selectRaw("NULL::uuid AS {$alias}");
        }

        $when = [];
        $bindings = [];
        foreach ($pairs as $key => $publicId) {
            $when[] = 'WHEN ? THEN CAST(? AS uuid)';
            $bindings[] = $key;
            $bindings[] = $publicId;
        }

        return $query->selectRaw(
            "CASE {$sourceColumn} ".implode(' ', $when)." ELSE NULL END AS {$alias}",
            $bindings,
        );
    }

    /** @param array<int|string,string> $labels */
    public static function projectLabels(Builder $query, string $sourceColumn, string $alias, array $labels): Builder
    {
        $pairs = [];
        foreach ($labels as $key => $label) {
            if ((is_int($key) || ctype_digit((string) $key)) && (int) $key > 0 && is_string($label) && $label !== '') {
                $pairs[(int) $key] = $label;
            }
        }
        if ($pairs === []) {
            return $query->selectRaw("NULL::text AS {$alias}");
        }

        $when = [];
        $bindings = [];
        foreach ($pairs as $key => $label) {
            $when[] = 'WHEN ? THEN ?';
            $bindings[] = $key;
            $bindings[] = $label;
        }

        return $query->selectRaw(
            "CASE {$sourceColumn} ".implode(' ', $when)." ELSE NULL END AS {$alias}",
            $bindings,
        );
    }
}
