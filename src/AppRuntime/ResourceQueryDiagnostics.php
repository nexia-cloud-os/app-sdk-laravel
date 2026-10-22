<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

/** Data-only validation of public reference queries, not model list-query fields. */
final class ResourceQueryDiagnostics
{
    public static function issues(array $contracts): array
    {
        $issues = [];
        foreach ($contracts as $index => $contract) {
            if (! array_key_exists('query', $contract)) {
                continue;
            }
            $resource = $contract['key'] ?? (string) $index;
            $path = 'contracts.'.(is_string($resource) && preg_match('/\A[a-zA-Z0-9_.-]{1,160}\z/D', $resource) ? $resource : $index).'.query';
            $add = static function (string $suffix, string $rule) use (&$issues, $path, $index): void {
                $field = $path.$suffix;
                if (strlen($field) > 240 || ! preg_match('/\A[a-zA-Z0-9_.:-]+\z/D', $field)) {
                    $field = 'contracts.'.$index.'.query';
                }
                $issues[] = ['field' => $field, 'rule' => $rule, 'limit' => null, 'actual' => null];
            };
            $query = $contract['query'];
            if (! is_array($query) || ! is_bool($query['resolve'] ?? null) || ! is_bool($query['paged'] ?? null)) {
                $add('', 'query_invalid');
                continue;
            }
            if ($query['paged'] && ! $query['resolve']) {
                $add('.paged', 'query_requires_resolve');
            }
            foreach (['filterable', 'sortable', 'searchable'] as $kind) {
                $keys = $query[$kind] ?? null;
                if (! is_array($keys) || ! array_is_list($keys) || count($keys) > 1000) {
                    $add('.'.$kind, 'query_invalid');
                    continue;
                }
                if (! $query['paged'] && $keys !== []) {
                    $add('.'.$kind, 'query_requires_paged');
                }
                $seen = [];
                foreach ($keys as $position => $key) {
                    if (! is_string($key) || $key === '' || strlen($key) > 80) {
                        $add('.'.$kind.'.'.$position, 'query_invalid');
                        continue;
                    }
                    if (isset($seen[$key])) {
                        $add('.'.$kind.'.'.$position, 'query_duplicate');
                    }
                    $seen[$key] = true;
                    if (! array_key_exists($key, is_array($contract['fields'] ?? null) ? $contract['fields'] : [])) {
                        $add('.'.$kind.'.'.$key, 'query_field_undeclared');
                    }
                }
            }
        }

        return $issues;
    }
}
