<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * App-neutral binding from a stable App business intent to an executable
 * Process definition key. Apps never receive or persist Core database ids.
 */
final class ProcessStartBindingDescriptor implements AppDescriptor
{
    public readonly string $key;

    public function __construct(
        public readonly string $appKey,
        public readonly string $bindingKey,
        public readonly string $definitionKey,
        public readonly string $processId,
        public readonly string $resourceKey,
        public readonly string $startPermissionKey,
        public readonly string $labelKey,
        public readonly string $version = '1.0',
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        foreach ([
            'app_key' => $appKey,
            'binding_key' => $bindingKey,
            'definition_key' => $definitionKey,
            'process_id' => $processId,
            'resource_key' => $resourceKey,
            'start_permission_key' => $startPermissionKey,
            'label_key' => $labelKey,
            'version' => $version,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new \InvalidArgumentException("ProcessStartBindingDescriptor {$field} must be non-empty.");
            }
        }

        if (! str_starts_with($resourceKey, $appKey.'.')) {
            throw new \InvalidArgumentException('Process start binding resource_key must be owned by app_key.');
        }
        if (! str_starts_with($definitionKey, $appKey.'.')) {
            throw new \InvalidArgumentException('Process start binding definition_key must be owned by app_key.');
        }
        if (! str_starts_with($startPermissionKey, $appKey.'.')) {
            throw new \InvalidArgumentException('Process start binding start_permission_key must be owned by app_key.');
        }

        $this->key = $appKey.'.'.$bindingKey;
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
