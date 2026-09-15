<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

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
    ) {}

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
