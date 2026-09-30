<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use InvalidArgumentException;

/** Versioned declaration requirements, distinct from App releases and execution permission. */
final class RuntimeRequirements
{
    public const RUNTIME = 'nexia-php84-v1';

    public const CONTRACTS = ['catalog' => 1, 'resource' => 1, 'action' => 1, 'settings' => 1, 'event' => 1];

    // Declaration support does not promise HTTP, settings, Job or Event execution.
    public const CAPABILITIES = ['resource.catalog', 'action.catalog', 'settings.catalog', 'event.catalog'];

    /** Enforce the selected sandbox host's support, before loading App PHP. */
    public static function validate(array $value, string $appKey): array
    {
        self::validateDeclaration($value, $appKey);
        if ($value['runtime'] !== self::RUNTIME || count($value['contracts']) !== count(self::CONTRACTS)) {
            throw new InvalidArgumentException('Unsupported platform contract or Runtime.');
        }
        foreach (self::CONTRACTS as $name => $version) {
            if (($value['contracts'][$name] ?? null) !== $version) {
                throw new InvalidArgumentException('Unsupported platform contract version.');
            }
        }
        if (array_diff($value['required_capabilities'], self::CAPABILITIES) !== []) {
            throw new InvalidArgumentException('Required capability is not supported by this Runtime.');
        }
        foreach ($value['integrations'] ?? [] as $entry) {
            if ($entry['required']) {
                throw new InvalidArgumentException('Required integration is not supported by this Runtime.');
            }
        }

        return $value;
    }

    /** Parse the declaration without choosing a host or granting execution support. */
    public static function validateDeclaration(array $value, string $appKey): array
    {
        if (array_diff(array_keys($value), ['schema_version', 'runtime', 'contracts', 'required_capabilities', 'optional_capabilities', 'integrations']) !== []
            || ($value['schema_version'] ?? null) !== 1
            || ! is_string($value['runtime'] ?? null) || ! preg_match('/\A[a-z][a-z0-9-]{0,99}\z/D', $value['runtime'])
            || ! is_array($value['contracts'] ?? null) || $value['contracts'] === [] || count($value['contracts']) > 100) {
            throw new InvalidArgumentException('Invalid platform declaration.');
        }
        foreach ($value['contracts'] as $name => $version) {
            if (! is_string($name) || ! preg_match('/\A[a-z][a-z0-9_-]{0,99}\z/D', $name) || ! is_int($version) || $version < 1) {
                throw new InvalidArgumentException('Invalid platform contract version.');
            }
        }
        foreach (['required_capabilities', 'optional_capabilities'] as $field) {
            $keys = $value[$field] ?? null;
            if (! is_array($keys) || ! array_is_list($keys) || count($keys) > 100) {
                throw new InvalidArgumentException('Capabilities must be bounded lists.');
            }
            $seen = [];
            foreach ($keys as $key) {
                if (! is_string($key) || ! preg_match('/\A[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $key) || isset($seen[$key])) {
                    throw new InvalidArgumentException('Invalid or duplicate capability.');
                }
                $seen[$key] = true;
            }
        }
        if (array_intersect($value['required_capabilities'], $value['optional_capabilities']) !== []) {
            throw new InvalidArgumentException('A capability cannot be both required and optional.');
        }
        $integrations = $value['integrations'] ?? [];
        if (! is_array($integrations) || ! array_is_list($integrations) || count($integrations) > 100) {
            throw new InvalidArgumentException('Integrations must be a bounded list.');
        }
        $seen = [];
        foreach ($integrations as $entry) {
            if (! is_array($entry) || array_diff(array_keys($entry), ['app', 'kind', 'key', 'version', 'required']) !== []
                || ! is_string($entry['app'] ?? null) || ! preg_match('/\A[a-z][a-z0-9-]*\z/D', $entry['app'])
                || $entry['app'] === $appKey || ! in_array($entry['kind'] ?? null, ['resource', 'action', 'event'], true)
                || ! is_string($entry['key'] ?? null) || ! str_starts_with($entry['key'], $entry['app'].'.')
                || ! preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $entry['key'])
                || ! is_string($entry['version'] ?? null) || ! preg_match('/\A[1-9][0-9]*\.[0-9]+\z/D', $entry['version'])
                || ! is_bool($entry['required'] ?? null)) {
                throw new InvalidArgumentException('Invalid public integration contract.');
            }
            $key = $entry['kind'].':'.$entry['key'];
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Duplicate integration contract.');
            }
            $seen[$key] = true;
        }

        return $value;
    }
}
