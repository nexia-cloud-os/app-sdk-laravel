<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

/** Data-only checks shared by isolated extraction and Core acceptance. */
final class StandardCatalogPolicy
{
    public static function validate(array $catalog): void
    {
        if (! isset($catalog['requirements'])) {
            return; // Existing Catalog v1 producers retain their current compatibility path.
        }
        RuntimeRequirements::validate($catalog['requirements'], $catalog['app_key']);
        $permissions = array_column($catalog['permissions'], null, 'key');
        $resources = array_column($catalog['resources'], null, 'key');
        $bindings = [];
        foreach ($catalog['route_bindings'] ?? [] as $binding) {
            $identity = $binding['key'].':'.$binding['operation'];
            if (isset($bindings[$identity])) {
                throw new CatalogValidationException('catalog_reference_invalid');
            }
            $bindings[$identity] = $binding;
        }
        foreach ($catalog['contracts'] ?? [] as $resource) {
            if (($resource['version'] ?? null) !== '1.0') {
                throw new CatalogValidationException('catalog_contract_unsupported');
            }
            foreach ($resource['actions'] ?? [] as $action) {
                if (array_key_exists('execution', $action)) {
                    \Nexia\Actions\ActionExecutionContract::fromArray($action['execution']);
                }
            }
            foreach ($resource['events'] ?? [] as $event) {
                if (($event['schema_version'] ?? null) !== 1) {
                    throw new CatalogValidationException('catalog_contract_unsupported');
                }
            }
        }
        foreach ($catalog['standalone_events'] ?? [] as $event) {
            if (($event['schema_version'] ?? null) !== 1) {
                throw new CatalogValidationException('catalog_contract_unsupported');
            }
        }
        foreach ($catalog['actions'] ?? [] as $entry) {
            $action = $entry['definition'];
            $binding = $bindings[$entry['key'].':action'] ?? null;
            if (! isset($permissions[$action['permission']])
                || ($permissions[$action['permission']]['lifecycle'] ?? null) !== 'active'
                || ($entry['resource_key'] !== null && ! isset($resources[$entry['resource_key']]))
                || $binding === null || $binding['method'] !== $action['method'] || $binding['path'] !== $action['path']) {
                throw new CatalogValidationException('catalog_reference_invalid');
            }
        }
        foreach ($catalog['shell']['resources'] ?? [] as $resource) {
            if (! isset($resources[$resource['key']])) {
                throw new CatalogValidationException('catalog_reference_invalid');
            }
        }
    }
}
