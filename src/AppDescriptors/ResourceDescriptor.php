<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;

/**
 * App-contributed resource descriptor.
 *
 * Field and search schemas describe Builder and picker surfaces; they are not
 * authoritative storage schemas. The owning App remains the source of truth
 * for resource persistence and validation. A cross-App picker field uses
 * type `resource_reference` and declares accepted_resource_keys,
 * selector_purpose, and selector_permissions in its field definition.
 */
final class ResourceDescriptor implements AppDescriptor
{
    /**
     * @param  string|null  $labelKey  i18n key naming the resource itself. Host
     *                                 surfaces that list many resources' contributions side by side — the
     *                                 process trigger catalog most of all — need the subject a contribution
     *                                 belongs to, and a resource key is an identifier, not a label. Optional
     *                                 so existing contributions keep resolving; a host that has no label
     *                                 shows no subject rather than a raw key.
     * @param  array<string, mixed>  $fieldSchema  A direct field may opt into
     *                                             `composition` metadata: selectable, groupable, aggregations, and
     *                                             time_buckets. This is capability metadata only; Core still applies the
     *                                             actor's row and field authorization before it exposes or queries it.
     * @param  array<string, mixed>  $searchSchema
     * @param  list<ResourceLifecycleEventDescriptor>  $lifecycleEvents
     */
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly ?string $labelKey = null,
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly array $fieldSchema = [],
        public readonly array $searchSchema = [],
        public readonly array $lifecycleEvents = [],
        public readonly bool $publicForBuilder = true,
        /** @var list<ResourceActionDescriptor> App-owned operations beyond ordinary Resource CRUD. */
        public readonly array $actions = [],
        public readonly ?ResourceMutationDescriptor $mutation = null,
        /** Public contract discovery only, never data access. Null preserves the existing visibility. */
        public readonly ?bool $publicForIntegration = null,
    ) {}

    public function isPublicForIntegration(): bool
    {
        return $this->publicForIntegration ?? $this->publicForBuilder;
    }

    /** Public discovery metadata only; it grants no data or action authority. */
    public function integrationContract(): ?array
    {
        if (! $this->isPublicForIntegration() || $this->status === DescriptorStatus::Removed) {
            return null;
        }

        return [
            'key' => $this->key, 'version' => $this->version, 'status' => $this->status->value,
            'label_key' => $this->labelKey, 'fields' => (object) $this->fieldSchema,
            'search' => (object) $this->searchSchema,
            'actions' => array_map(static fn ($action): array => [
                'key' => $action->key, 'permission' => $action->permission,
                'effect' => $action->effect->value, 'input_schema' => $action->inputSchema,
            ], $this->actions),
            'events' => array_values(array_map(static fn ($event): array => [
                'key' => $event->key, 'schema_version' => $event->schemaVersion,
                'stability' => $event->stability, 'payload_schema' => (object) $event->payloadSchema,
            ], array_filter($this->lifecycleEvents, static fn ($event): bool => $event->publicForComposition))),
        ];
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
