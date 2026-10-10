<?php

declare(strict_types=1);

namespace Nexia\ResourceComposition;

/** Shared descriptor eligibility; hosts still prove columns and authorize every query. */
final class DescriptorCompositionFields
{
    /**
     * @param array<string,array<string,mixed>> $declaredFields
     * @param array<string,array<string,mixed>> $resolvedFields Host-proven queryability and field source.
     * @param array<string,string> $filterable Exact list-filter labels.
     * @param callable(string):?string $translate Null means no translation is published.
     * @return list<array<string,mixed>>
     */
    public static function build(array $declaredFields, array $resolvedFields, array $filterable, callable $translate): array
    {
        $fields = [];
        foreach ($declaredFields as $key => $declared) {
            if (! is_string($key)
                || ! is_array($declared)
                || ! self::safeDirectField($key, $declared, $resolvedFields[$key] ?? null)) {
                continue;
            }

            $composition = is_array($declared['composition'] ?? null) ? $declared['composition'] : [];
            $selectable = $key === 'public_id' || ($composition['selectable'] ?? false) === true;
            $references = ($declared['type'] ?? null) === 'resource_reference' && is_array($declared['accepted_resource_keys'] ?? null)
                ? array_values(array_unique(array_filter($declared['accepted_resource_keys'], 'is_string')))
                : [];
            $reference = count($references) === 1 ? $references[0] : null;
            // A proven direct reference is a join binding, not permission to display its value.
            $relationshipBinding = ($composition['relationship_binding'] ?? false) === true || $reference !== null;
            $operators = isset($filterable[$key]) ? ['eq', 'in'] : [];
            if (isset($filterable[$key]) || $selectable) {
                $operators = array_values(array_unique(array_filter(
                    is_array($composition['filter_operators'] ?? null)
                        ? $composition['filter_operators']
                        : $operators,
                    static fn (mixed $operator): bool => in_array($operator, [
                        'eq', 'in', 'neq', 'not_in', 'gt', 'gte', 'lt', 'lte',
                        'between', 'is_null', 'not_null', 'contains', 'not_contains',
                        'starts_with', 'ends_with',
                    ], true),
                )));
            }

            // A descriptor field being direct and queryable does not prove
            // that its value is authorized for this actor. Some Resources
            // redact otherwise-direct serializer fields behind an additional
            // permission. Until the owner publishes field-level authority,
            // admit only the public identity as output. Exact list filters may
            // remain as filter-only intent because they add a SQL predicate
            // without returning the protected value.
            if (! $selectable && $operators === [] && ! $relationshipBinding) {
                continue;
            }

            $field = [
                'key' => $key,
                'type' => (($declared['type'] ?? null) === 'string' && in_array($declared['format'] ?? null, ['date', 'datetime', 'date-time'], true))
                    ? (($declared['format'] ?? null) === 'date-time' ? 'datetime' : $declared['format'])
                    : (string) ($declared['type'] ?? 'string'),
                'nullable' => ($declared['nullable'] ?? false) === true,
                'selectable' => $selectable,
                'operators' => $operators,
                'column' => $key,
            ];
            if ($relationshipBinding) {
                $field['relationship_binding'] = true;
            }
            $labelKey = $declared['label_key'] ?? $filterable[$key] ?? null;
            if (is_string($labelKey) && $labelKey !== '') {
                $field['label'] = $translate($labelKey) ?? $labelKey;
            }
            if ($reference !== null) {
                $field['reference_resource_key'] = $reference;
                $field['label'] ??= $key;
            }
            foreach (['format', 'enum'] as $optional) {
                if (array_key_exists($optional, $declared)) {
                    $field[$optional] = $declared[$optional];
                }
            }
            if (is_array($composition['temporal_interval'] ?? null)) {
                $field['temporal_interval'] = $composition['temporal_interval'];
            }
            if (($composition['groupable'] ?? false) === true) {
                $field['groupable'] = true;
            }
            $aggregations = array_values(array_unique(array_filter(
                is_array($composition['aggregations'] ?? null) ? $composition['aggregations'] : [],
                static fn (mixed $operation): bool => in_array($operation, ['distinct_count', 'sum', 'avg', 'min', 'max', 'median'], true),
            )));
            if ($aggregations !== []) {
                $field['aggregations'] = $aggregations;
                $field['unit'] = is_string($composition['unit'] ?? null) ? $composition['unit'] : null;
                if (array_key_exists('required_dimensions', $composition)) {
                    $field['required_dimensions'] = $composition['required_dimensions'];
                }
            }
            $timeBuckets = array_values(array_unique(array_filter(
                is_array($composition['time_buckets'] ?? null) ? $composition['time_buckets'] : [],
                static fn (mixed $bucket): bool => in_array($bucket, ['day', 'week', 'month', 'quarter', 'year'], true),
            )));
            if ($timeBuckets !== []) {
                $field['time_buckets'] = $timeBuckets;
            }
            if (is_array($declared['enum_labels'] ?? null)) {
                $field['enum_labels'] = [];
                foreach ($declared['enum_labels'] as $value => $labelKey) {
                    if (is_string($labelKey) && ($label = $translate($labelKey)) !== null) {
                        $field['enum_labels'][(string) $value] = $label;
                    }
                }
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /** @param array<string,mixed> $declared */
    private static function safeDirectField(string $key, array $declared, mixed $resolved): bool
    {
        if (! is_array($resolved)
            || ($resolved['field_source'] ?? null) !== 'descriptor'
            || ($resolved['queryable'] ?? false) !== true
            || ($declared['derived'] ?? false) === true) {
            return false;
        }

        if ($key === 'id'
            || ($key !== 'public_id' && str_ends_with($key, '_id') && ! str_ends_with($key, '_public_id'))
            || preg_match('/(?:^|_)(?:hash|ciphertext|secret|password|token|lock_version)(?:_|$)/', $key) === 1
            || preg_match('/^(?:created|updated|deleted)_by/', $key) === 1) {
            return false;
        }

        return in_array((string) ($declared['type'] ?? 'string'), [
            'boolean',
            'date',
            'decimal',
            'datetime',
            'integer',
            'number',
            'resource_reference',
            'string',
            'time',
            'uuid',
        ], true);
    }
}
