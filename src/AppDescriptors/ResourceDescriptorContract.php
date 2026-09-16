<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\ResourceReference\PartySelectionConstraint;
use Nexia\ResourceReference\ResourceRef;

/** Runtime validation for the public Resource descriptor result. */
final class ResourceDescriptorContract
{
    /**
     * @param  array<mixed>  $resources
     * @return list<ResourceDescriptor>
     */
    public static function assertValid(string $contributor, array $resources): array
    {
        $validated = [];

        foreach ($resources as $resourceIndex => $resource) {
            if (! $resource instanceof ResourceDescriptor) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    'must return only ResourceDescriptor instances.',
                    (string) $resourceIndex,
                );
            }

            self::assertReferenceFields($contributor, $resourceIndex, $resource);
            self::assertActions($contributor, $resourceIndex, $resource);
            if ($resource->mutation !== null && ! $resource->mutation instanceof ResourceMutationDescriptor) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] mutation must be a ResourceMutationDescriptor.",
                    "{$resourceIndex}.mutation",
                );
            }

            foreach ($resource->lifecycleEvents as $eventIndex => $event) {
                if (! $event instanceof ResourceLifecycleEventDescriptor) {
                    throw DescriptorValidationException::contribution(
                        $contributor,
                        "resource [{$resource->key}] must contain only ResourceLifecycleEventDescriptor instances.",
                        "{$resourceIndex}.lifecycleEvents.{$eventIndex}",
                    );
                }

                if (! $event->publicForComposition) {
                    continue;
                }

                if (trim($event->labelKey) === '') {
                    throw DescriptorValidationException::lifecycleEvent(
                        $resource->key.'.'.$event->key,
                        'requires a non-empty labelKey.',
                    );
                }

                // Label *ambiguity* is deliberately not asserted here. This
                // contract is the installation gate, and a failure omits the
                // whole resource contribution from the live catalog — a worse
                // authoring outcome than a poorly named field. Ambiguity is a
                // pre-flight finding instead; see
                // PublicEventPayloadSchema::assertDistinctLabelKeys().
                PublicEventPayloadSchema::assertValid(
                    $resource->key.'.'.$event->key,
                    $event->payloadSchema,
                );
            }

            $validated[] = $resource;
        }

        return $validated;
    }

    private static function assertActions(string $contributor, int|string $resourceIndex, ResourceDescriptor $resource): void
    {
        $seen = [];
        foreach ($resource->actions as $actionIndex => $action) {
            if (! $action instanceof ResourceActionDescriptor) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] must contain only ResourceActionDescriptor instances.",
                    "{$resourceIndex}.actions.{$actionIndex}",
                );
            }
            if (in_array($action->key, ['create', 'update', 'delete'], true)) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] action [{$action->key}] duplicates ordinary Resource CRUD.",
                    "{$resourceIndex}.actions.{$actionIndex}.key",
                );
            }
            if (! str_starts_with($action->permission, $resource->key.'.')) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] action [{$action->key}] permission must belong to the Resource.",
                    "{$resourceIndex}.actions.{$actionIndex}.permission",
                );
            }
            if (isset($seen[$action->key])) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] action [{$action->key}] is declared more than once.",
                    "{$resourceIndex}.actions.{$actionIndex}.key",
                );
            }
            $seen[$action->key] = true;
        }
    }

    private static function assertReferenceFields(
        string $contributor,
        int|string $resourceIndex,
        ResourceDescriptor $resource,
    ): void {
        $separator = strpos($resource->key, '.');
        $appKey = $separator === false ? '' : substr($resource->key, 0, $separator);
        if (! ResourceRef::hasCanonicalIdentity($appKey, $resource->key)) {
            throw DescriptorValidationException::contribution(
                $contributor,
                'Resource descriptor keys used by selectors must be valid and at most 160 characters.',
                (string) $resourceIndex,
            );
        }

        foreach ($resource->fieldSchema as $fieldKey => $field) {
            if (is_array($field)
                && array_key_exists('party_selection', $field)
                && ($field['type'] ?? null) !== 'resource_reference') {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] field [{$fieldKey}] may declare party_selection only for a Resource Reference.",
                    "{$resourceIndex}.fieldSchema.{$fieldKey}.party_selection",
                );
            }

            if (! is_array($field) || ($field['type'] ?? null) !== 'resource_reference') {
                continue;
            }

            $path = "{$resourceIndex}.fieldSchema.{$fieldKey}";
            if (! is_string($fieldKey)
                || mb_strlen($fieldKey) > 160
                || preg_match('/^[a-z][a-z0-9._-]*$/', $fieldKey) !== 1) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field keys must be valid and at most 160 characters.",
                    $path,
                );
            }
            $accepted = $field['accepted_resource_keys'] ?? null;
            if (! is_array($accepted)
                || ! array_is_list($accepted)
                || $accepted === []) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] must declare unique accepted_resource_keys.",
                    $path.'.accepted_resource_keys',
                );
            }
            foreach ($accepted as $resourceKey) {
                $acceptedSeparator = is_string($resourceKey) ? strpos($resourceKey, '.') : false;
                $acceptedAppKey = $acceptedSeparator === false ? '' : substr($resourceKey, 0, $acceptedSeparator);
                if (! is_string($resourceKey)
                    || ! ResourceRef::hasCanonicalIdentity($acceptedAppKey, $resourceKey)) {
                    throw DescriptorValidationException::contribution(
                        $contributor,
                        "resource [{$resource->key}] Resource Reference field [{$fieldKey}] contains an invalid accepted Resource key.",
                        $path.'.accepted_resource_keys',
                    );
                }
            }
            if (count($accepted) !== count(array_unique($accepted, SORT_STRING))) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] accepted_resource_keys must be unique.",
                    $path.'.accepted_resource_keys',
                );
            }

            $hasTargetResourceKeyField = array_key_exists('target_resource_key_field', $field);
            $targetResourceKeyField = $field['target_resource_key_field'] ?? null;
            if ($hasTargetResourceKeyField) {
                $targetField = is_string($targetResourceKeyField)
                    ? ($resource->fieldSchema[$targetResourceKeyField] ?? null)
                    : null;
                $targetComposition = is_array($targetField)
                    && is_array($targetField['composition'] ?? null)
                    ? $targetField['composition']
                    : [];

                if (! is_string($targetResourceKeyField)
                    || preg_match('/\A[a-z][a-z0-9_]{0,159}\z/D', $targetResourceKeyField) !== 1
                    || count($accepted) < 2
                    || ($field['derived'] ?? false) === true
                    || ! is_array($targetField)
                    || ($targetField['type'] ?? null) !== 'string'
                    || ($targetField['derived'] ?? false) === true
                    || (($targetComposition['selectable'] ?? false) !== true
                        && ($targetComposition['relationship_binding'] ?? false) !== true)) {
                    throw DescriptorValidationException::contribution(
                        $contributor,
                        "resource [{$resource->key}] Resource Reference field [{$fieldKey}] target_resource_key_field requires a stored multi-target reference and an owner-published composition string discriminator field.",
                        $path.'.target_resource_key_field',
                    );
                }
            }

            $temporalInterval = is_array($field['composition'] ?? null)
                ? ($field['composition']['temporal_interval'] ?? null)
                : null;
            if ($temporalInterval !== null) {
                $start = is_array($temporalInterval) ? ($temporalInterval['start_field'] ?? null) : null;
                $end = is_array($temporalInterval) ? ($temporalInterval['end_field'] ?? null) : null;
                $startField = is_string($start) ? ($resource->fieldSchema[$start] ?? null) : null;
                $endField = is_string($end) ? ($resource->fieldSchema[$end] ?? null) : null;
                if (! is_array($temporalInterval)
                    || array_diff(array_keys($temporalInterval), ['start_field', 'end_field', 'end_bound', 'null_start', 'null_end']) !== []
                    || ! is_string($start)
                    || ! is_string($end)
                    || ! is_array($startField)
                    || ! is_array($endField)
                    || ($startField['type'] ?? null) !== 'string'
                    || ($startField['format'] ?? null) !== 'date'
                    || ($endField['type'] ?? null) !== 'string'
                    || ($endField['format'] ?? null) !== 'date'
                    || ($temporalInterval['end_bound'] ?? null) !== 'exclusive'
                    || ($temporalInterval['null_start'] ?? null) !== 'reject'
                    || ($temporalInterval['null_end'] ?? null) !== 'open') {
                    throw DescriptorValidationException::contribution(
                        $contributor,
                        "resource [{$resource->key}] Resource Reference field [{$fieldKey}] temporal_interval requires direct date bounds with [start, end) semantics.",
                        $path.'.composition.temporal_interval',
                    );
                }
            }

            $purpose = $field['selector_purpose'] ?? null;
            if (! is_string($purpose)
                || mb_strlen($purpose) > 160
                || ! str_starts_with($purpose, $appKey.'.')
                || preg_match('/^[a-z][a-z0-9-]*\.[a-z][a-z0-9._-]*$/', $purpose) !== 1) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] selector_purpose must be owned by its App.",
                    $path.'.selector_purpose',
                );
            }

            $permissions = $field['selector_permissions'] ?? null;
            if (! is_array($permissions)
                || ! array_is_list($permissions)
                || $permissions === []) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] must declare unique selector_permissions.",
                    $path.'.selector_permissions',
                );
            }
            foreach ($permissions as $permission) {
                if (! is_string($permission)
                    || ! str_starts_with($permission, $appKey.'.')
                    || preg_match('/^[a-z][a-z0-9-]*\.[a-z][a-z0-9._-]*$/', $permission) !== 1) {
                    throw DescriptorValidationException::contribution(
                        $contributor,
                        "resource [{$resource->key}] Resource Reference field [{$fieldKey}] selector_permissions must be owned by its App.",
                        $path.'.selector_permissions',
                    );
                }
            }
            if (count($permissions) !== count(array_unique($permissions, SORT_STRING))) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] selector_permissions must be unique.",
                    $path.'.selector_permissions',
                );
            }

            $acceptsParty = in_array('directory.party', $accepted, true);
            if ($acceptsParty && ! array_key_exists('party_selection', $field)) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] accepting directory.party must declare party_selection.",
                    $path.'.party_selection',
                );
            }
            if (! array_key_exists('party_selection', $field)) {
                continue;
            }
            if (! $acceptsParty || ! is_array($field['party_selection'])) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] party_selection requires directory.party and a typed constraint.",
                    $path.'.party_selection',
                );
            }

            try {
                PartySelectionConstraint::fromArray($field['party_selection']);
            } catch (\InvalidArgumentException $exception) {
                throw DescriptorValidationException::contribution(
                    $contributor,
                    "resource [{$resource->key}] Resource Reference field [{$fieldKey}] has an invalid party_selection: {$exception->getMessage()}",
                    $path.'.party_selection',
                );
            }
        }
    }
}
