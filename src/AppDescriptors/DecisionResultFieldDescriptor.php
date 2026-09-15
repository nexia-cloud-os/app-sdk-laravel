<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * One recommended result field inside a {@see DecisionResultTemplateDescriptor}.
 *
 * Field descriptors are starter-shape metadata only. They name a stable
 * `id`, a translation key for the human-facing label, the storage `type`
 * (matching the Core fixed `string | number | boolean` subset of DMN
 * output types), and the Core fixed `purpose` from `plain | assignmentGroup`.
 *
 * For `assignmentGroup`, the AssignmentGroup catalog (runtime tenant/org
 * table) is the source of truth. For `plain`, no extra metadata applies.
 *
 * @see docs/decisions/dmn.md
 * @see docs/reference/APP-DESCRIPTORS.md — current Descriptor Categories
 */
final class DecisionResultFieldDescriptor
{
    public const SUPPORTED_TYPES = ['string', 'number', 'boolean'];

    public const SUPPORTED_PURPOSES = ['plain', 'assignmentGroup'];

    /**
     * @param  string  $id  Stable field identifier (e.g. 'assignee_group').
     * @param  string  $labelKey  Translation key for the field label.
     * @param  string  $type  One of {@see self::SUPPORTED_TYPES}.
     * @param  string  $purpose  One of {@see self::SUPPORTED_PURPOSES}.
     * @param  string|null  $notesKey  Optional translation key for explanatory notes shown in the picker.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $labelKey,
        public readonly string $type,
        public readonly string $purpose,
        public readonly ?string $notesKey = null,
    ) {
        if (trim($id) === '' || trim($labelKey) === '') {
            throw new \InvalidArgumentException(
                'DecisionResultFieldDescriptor id and label_key must be non-empty strings.',
            );
        }

        if ($notesKey !== null && trim($notesKey) === '') {
            throw new \InvalidArgumentException(
                "DecisionResultFieldDescriptor [{$id}] notes_key must be null or a non-empty string.",
            );
        }

        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException(
                "DecisionResultFieldDescriptor [{$id}] type [{$type}] is not supported. Expected one of: "
                .implode(', ', self::SUPPORTED_TYPES),
            );
        }

        if (! in_array($purpose, self::SUPPORTED_PURPOSES, true)) {
            throw new \InvalidArgumentException(
                "DecisionResultFieldDescriptor [{$id}] purpose [{$purpose}] is not supported. Expected one of: "
                .implode(', ', self::SUPPORTED_PURPOSES),
            );
        }

        // `assignmentGroup` only makes sense on a string field. Reject an
        // incompatible combination here so a misauthored descriptor fails fast
        // at boot instead of producing a publish-time error later.
        if ($purpose === 'assignmentGroup' && $type !== 'string') {
            throw new \InvalidArgumentException(
                "DecisionResultFieldDescriptor [{$id}] purpose [{$purpose}] requires type [string]; got type [{$type}].",
            );
        }
    }
}
