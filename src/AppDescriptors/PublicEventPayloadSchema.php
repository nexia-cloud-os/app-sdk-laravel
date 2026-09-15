<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\Events\HandoffResultState;

/**
 * Canonical public lifecycle-event payload schema contract.
 *
 * Apps may use {@see withLabelKeys()} to attach an App-owned label family to
 * an existing schema. Every node is labelled, including object properties,
 * array items, and composition variants. Existing label aliases remain
 * readable for compatibility; newly generated labels use `labelKey`.
 */
final class PublicEventPayloadSchema
{
    private const LABEL_KEYS = ['labelKey', 'label_key', 'label_key'];

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public static function withLabelKeys(array $schema, string $labelKeyPrefix): array
    {
        $prefix = trim($labelKeyPrefix, '. ');

        if ($prefix === '') {
            throw DescriptorValidationException::lifecycleEvent(
                'unknown',
                'requires a non-empty payload label-key prefix.',
            );
        }

        foreach ($schema as $field => $node) {
            self::assertFieldKey($field, 'unknown', null);
            $schema[$field] = self::labelNode($node, $prefix, $field, $field, 'unknown');
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function assertValid(string $eventKey, array $schema): void
    {
        EventPayloadSchema::assertValid($eventKey, $schema);
        self::assertHandoffResultStates($eventKey, $schema);

        foreach ($schema as $field => $node) {
            self::assertFieldKey($field, $eventKey, null);
            self::assertNode($node, $eventKey, $field);
        }
    }

    /**
     * Validate every App-handoff result vocabulary declared by a lifecycle
     * event, including internal events that are not exposed for composition.
     *
     * @param  array<string, mixed>  $schema
     */
    public static function assertHandoffResultStates(string $eventKey, array $schema): void
    {
        foreach ($schema as $field => $node) {
            self::assertHandoffResultStateNode($node, $eventKey, (string) $field, (string) $field);
        }
    }

    /**
     * Every named payload field must carry a label an author can tell apart
     * from its siblings and from the event itself. The trigger and decision
     * pickers render the event label and its sibling field labels side by side,
     * so a reused key produces several rows with one name and the author cannot
     * say which field a condition reads. `items` and composition variants
     * describe the same concept as the node they belong to, so they inherit its
     * label and are never compared against it.
     *
     * @param  array<string, mixed>  $schema
     */
    public static function assertDistinctLabelKeys(
        string $eventKey,
        string $eventLabelKey,
        array $schema,
    ): void {
        self::assertDistinctSiblingLabelKeys($eventKey, trim($eventLabelKey), $schema, null);
    }

    /** @param array<string, mixed> $container */
    private static function assertDistinctSiblingLabelKeys(
        string $eventKey,
        string $eventLabelKey,
        array $container,
        ?string $parentPath,
    ): void {
        /** @var array<string, string> $seen */
        $seen = [];

        foreach ($container as $field => $node) {
            if (! is_array($node)) {
                continue;
            }

            $path = $parentPath === null ? (string) $field : $parentPath.'.'.$field;
            $labelKey = self::labelKeyOf($node);

            if ($labelKey === null) {
                continue;
            }

            if ($eventLabelKey !== '' && $labelKey === $eventLabelKey) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    "must not reuse the event's own label key [{$labelKey}]; give the field its own label.",
                    $path,
                );
            }

            if (isset($seen[$labelKey])) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    "reuses label key [{$labelKey}] already used by sibling field [{$seen[$labelKey]}]; give each field its own label.",
                    $path,
                );
            }

            $seen[$labelKey] = $path;

            self::assertDistinctChildLabelKeys($eventKey, $eventLabelKey, $node, $path);
        }
    }

    /** @param array<string, mixed> $node */
    private static function assertDistinctChildLabelKeys(
        string $eventKey,
        string $eventLabelKey,
        array $node,
        string $path,
    ): void {
        foreach (['properties', 'fields'] as $container) {
            if (! isset($node[$container]) || ! is_array($node[$container])) {
                continue;
            }

            self::assertDistinctSiblingLabelKeys($eventKey, $eventLabelKey, $node[$container], $path);
        }

        if (isset($node['items']) && is_array($node['items'])) {
            self::assertDistinctChildLabelKeys($eventKey, $eventLabelKey, $node['items'], $path.'[]');
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $composition) {
            if (! isset($node[$composition]) || ! is_array($node[$composition])) {
                continue;
            }

            foreach ($node[$composition] as $index => $variant) {
                if (! is_array($variant)) {
                    continue;
                }

                self::assertDistinctChildLabelKeys(
                    $eventKey,
                    $eventLabelKey,
                    $variant,
                    $path.'.'.$composition.'.'.$index,
                );
            }
        }
    }

    private static function labelNode(
        mixed $node,
        string $prefix,
        string $field,
        string $path,
        string $eventKey,
    ): array {
        if (! is_array($node)) {
            throw DescriptorValidationException::lifecycleEvent(
                $eventKey,
                'must be an object.',
                $path,
            );
        }

        if (! self::hasLabelKey($node)) {
            $node['labelKey'] = $prefix.'.'.$field;
        }

        foreach (['properties', 'fields'] as $container) {
            if (! array_key_exists($container, $node)) {
                continue;
            }

            if (! is_array($node[$container])) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    "[{$container}] must be an object.",
                    $path,
                );
            }

            foreach ($node[$container] as $childField => $childNode) {
                self::assertFieldKey($childField, $eventKey, $path);
                $childPath = $path.'.'.$childField;
                $node[$container][$childField] = self::labelNode(
                    $childNode,
                    $prefix,
                    $childField,
                    $childPath,
                    $eventKey,
                );
            }
        }

        if (array_key_exists('items', $node)) {
            $node['items'] = self::labelNode(
                $node['items'],
                $prefix,
                $field,
                $path.'[]',
                $eventKey,
            );
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $composition) {
            if (! array_key_exists($composition, $node)) {
                continue;
            }

            if (! is_array($node[$composition])) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    "[{$composition}] must be an array.",
                    $path,
                );
            }

            foreach ($node[$composition] as $index => $variant) {
                $node[$composition][$index] = self::labelNode(
                    $variant,
                    $prefix,
                    $field,
                    $path.'.'.$composition.'.'.$index,
                    $eventKey,
                );
            }
        }

        return $node;
    }

    private static function assertNode(mixed $node, string $eventKey, string $path): void
    {
        if (! is_array($node)) {
            throw DescriptorValidationException::lifecycleEvent($eventKey, 'must be an object.', $path);
        }

        if (! self::hasLabelKey($node)) {
            throw DescriptorValidationException::lifecycleEvent($eventKey, 'requires labelKey.', $path);
        }

        foreach (['properties', 'fields'] as $container) {
            if (! array_key_exists($container, $node)) {
                continue;
            }

            if (! is_array($node[$container])) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    "[{$container}] must be an object.",
                    $path,
                );
            }

            foreach ($node[$container] as $childField => $childNode) {
                self::assertFieldKey($childField, $eventKey, $path);
                self::assertNode($childNode, $eventKey, $path.'.'.$childField);
            }
        }

        if (array_key_exists('items', $node)) {
            self::assertNode($node['items'], $eventKey, $path.'[]');
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $composition) {
            if (! array_key_exists($composition, $node)) {
                continue;
            }

            if (! is_array($node[$composition])) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    "[{$composition}] must be an array.",
                    $path,
                );
            }

            foreach ($node[$composition] as $index => $variant) {
                self::assertNode($variant, $eventKey, $path.'.'.$composition.'.'.$index);
            }
        }
    }

    private static function assertFieldKey(mixed $field, string $eventKey, ?string $parentPath): void
    {
        if (is_string($field) && trim($field) !== '') {
            return;
        }

        throw DescriptorValidationException::lifecycleEvent(
            $eventKey,
            'contains an invalid field key.',
            $parentPath,
        );
    }

    private static function assertHandoffResultStateNode(
        mixed $node,
        string $eventKey,
        string $field,
        string $path,
    ): void {
        if ($field === 'result_state') {
            self::assertCanonicalHandoffResultState($node, $eventKey, $path);
        }

        if (! is_array($node)) {
            return;
        }

        foreach (['properties', 'fields'] as $container) {
            if (! isset($node[$container]) || ! is_array($node[$container])) {
                continue;
            }

            foreach ($node[$container] as $childField => $childNode) {
                self::assertHandoffResultStateNode(
                    $childNode,
                    $eventKey,
                    (string) $childField,
                    $path.'.'.$childField,
                );
            }
        }

        if (isset($node['items']) && is_array($node['items'])) {
            self::assertHandoffResultStateNode($node['items'], $eventKey, $field, $path.'[]');
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $composition) {
            if (! isset($node[$composition]) || ! is_array($node[$composition])) {
                continue;
            }

            foreach ($node[$composition] as $index => $variant) {
                self::assertHandoffResultStateNode(
                    $variant,
                    $eventKey,
                    $field,
                    $path.'.'.$composition.'.'.$index,
                );
            }
        }
    }

    private static function assertCanonicalHandoffResultState(
        mixed $node,
        string $eventKey,
        string $path,
    ): void {
        if (! is_array($node) || ($node['type'] ?? null) !== 'string') {
            throw DescriptorValidationException::lifecycleEvent(
                $eventKey,
                'must declare type [string] for the canonical handoff result vocabulary.',
                $path,
            );
        }

        $allowed = $node['enum'] ?? null;

        if (! is_array($allowed) || ! array_is_list($allowed) || $allowed === []) {
            throw DescriptorValidationException::lifecycleEvent(
                $eventKey,
                'must declare a non-empty enum subset of the canonical handoff result vocabulary.',
                $path,
            );
        }

        foreach ($allowed as $state) {
            if (! is_string($state) || HandoffResultState::tryFrom($state) === null) {
                throw DescriptorValidationException::lifecycleEvent(
                    $eventKey,
                    'contains an undeclared handoff result state.',
                    $path,
                );
            }
        }

        if (count(array_unique($allowed)) !== count($allowed)) {
            throw DescriptorValidationException::lifecycleEvent(
                $eventKey,
                'must not contain duplicate handoff result states.',
                $path,
            );
        }
    }

    /** @param array<string, mixed> $node */
    private static function hasLabelKey(array $node): bool
    {
        return self::labelKeyOf($node) !== null;
    }

    /** @param array<string, mixed> $node */
    private static function labelKeyOf(array $node): ?string
    {
        foreach (self::LABEL_KEYS as $key) {
            if (isset($node[$key]) && is_string($node[$key]) && trim($node[$key]) !== '') {
                return trim($node[$key]);
            }
        }

        return null;
    }
}
