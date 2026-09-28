<?php

declare(strict_types=1);

namespace Nexia\Navigation;

use RuntimeException;

/** Selects menu placement; routes, labels and authority remain owned by declared screens. */
final class AppNavigation
{
    public static function validate(mixed $navigation, string $appKey): void
    {
        if (! is_array($navigation) || ! array_is_list($navigation) || count($navigation) > 1000) {
            throw new RuntimeException('App navigation must be a bounded list.');
        }
        $seen = [];
        foreach ($navigation as $entry) {
            $screen = is_array($entry) ? ($entry['screen'] ?? null) : null;
            if (! is_array($entry) || array_diff(array_keys($entry), ['screen', 'group', 'subgroup', 'sort', 'icon']) !== []
                || ! is_string($screen) || strlen($screen) > 191
                || ($screen !== $appKey && ! str_starts_with($screen, $appKey.'.') && ! str_starts_with($screen, $appKey.'-'))
                || ! preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $screen) || isset($seen[$screen])) {
                throw new RuntimeException('App navigation must select unique owned screen keys.');
            }
            if (array_key_exists('group', $entry)
                && ! in_array($entry['group'], [null, 'insights', 'management', 'operations', 'master-data', 'settings'], true)) {
                throw new RuntimeException('Unsupported App navigation group.');
            }
            foreach (['subgroup', 'icon'] as $field) {
                if (array_key_exists($field, $entry) && (! is_string($entry[$field])
                    || strlen($entry[$field]) > 100 || ! preg_match('/\A[a-z][a-z0-9-]*\z/D', $entry[$field]))) {
                    throw new RuntimeException('Invalid App navigation '.$field.'.');
                }
            }
            if (isset($entry['subgroup']) && ($entry['group'] ?? null) === null) {
                throw new RuntimeException('An App navigation subgroup requires a group.');
            }
            if (array_key_exists('sort', $entry) && (! is_int($entry['sort']) || $entry['sort'] < 0 || $entry['sort'] > 2147483647)) {
                throw new RuntimeException('App navigation sort must be a nonnegative integer.');
            }
            $seen[$screen] = true;
        }
    }

    /** @param list<array<string, mixed>> $screens @return list<array<string, mixed>> */
    public static function resolve(array $declaration, string $appKey, array $screens): array
    {
        if (! array_key_exists('navigation', $declaration)) {
            return $screens; // Existing packages keep their PHP menu until explicitly adopting JSON navigation.
        }
        self::validate($declaration['navigation'], $appKey);
        $known = [];
        foreach ($screens as $screen) {
            $id = $screen['id'] ?? null;
            if (! is_string($id) || ($screen['app_key'] ?? null) !== $appKey || isset($known[$id])) {
                throw new RuntimeException('Navigation screen ownership or identity is invalid.');
            }
            $known[$id] = $screen;
        }
        $items = [];
        foreach ($declaration['navigation'] as $entry) {
            $item = $known[$entry['screen']] ?? throw new RuntimeException('App navigation selects an undeclared screen.');
            foreach (['group' => 'group_id', 'subgroup' => 'subgroup_id', 'sort' => 'sort', 'icon' => 'icon'] as $field => $target) {
                if (array_key_exists($field, $entry)) {
                    $item[$target] = $entry[$field];
                }
            }
            // Moving out of an old group must not retain its subgroup accidentally.
            if (array_key_exists('group', $entry) && ! array_key_exists('subgroup', $entry)) {
                $item['subgroup_id'] = null;
            }
            $items[] = $item;
        }

        return $items;
    }
}
