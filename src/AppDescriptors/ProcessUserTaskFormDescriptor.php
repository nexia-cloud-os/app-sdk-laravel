<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;

/**
 * App-contributed human task form descriptor.
 *
 * A `ProcessUserTaskFormDescriptor` declares the schema a BPMN
 * `userTask` form captures. The descriptor is the only legitimate
 * source of task-form fields on the authoring surface and the schema
 * the runtime validates a completion payload against. Apps cannot
 * introduce new descriptor categories — the category set is closed.
 *
 * Rendering mode is either the Core schema renderer (`core_schema`) or
 * a documented slot-widget host (`slot_widget`). Core never imports an
 * app React component; complex app UI renders only through the
 * published `process.user_task.form` slot contract.
 *
 * Supported field types are Core-fixed:
 * `string`, `number`, `boolean`, `enum`, `date`, `dateTime`,
 * `resourceRef`, `document`.
 *
 * @see docs/reference/APP-DESCRIPTORS.md — current Descriptor Categories
 * @see docs/reference/APP-DESCRIPTORS.md — ProcessUserTaskFormDescriptor
 */
final class ProcessUserTaskFormDescriptor implements AppDescriptor
{
    public const SUPPORTED_FIELD_TYPES = [
        'string',
        'number',
        'boolean',
        'enum',
        'date',
        'dateTime',
        'resourceRef',
        'document',
    ];

    public const RENDERING_MODES = ['core_schema', 'slot_widget'];

    /** Value types a slot submission may expose to BPMN Activity IO. */
    public const SUPPORTED_OUTPUT_TYPES = [
        'string',
        'number',
        'boolean',
        'enum',
        'date',
        'dateTime',
        'resourceRef',
        'ResourceRef',
        'document',
        'UserRef',
        'list<UserRef>',
        'AssignmentGroupRef',
        'list<AssignmentGroupRef>',
    ];

    public const FORM_SLOT = 'process.user_task.form';

    /**
     * The descriptor `key` is also consumed by host installed-app resolution:
     * it already carries the `{appKey}.{...}` prefix, so the first
     * dot-separated segment is matched against the tenant's active app
     * installations.
     *
     * @param  string  $key  Stable descriptor key (e.g. `sample.candidate_review_form`); the first dot segment is the app key.
     * @param  string  $appKey  App key the form belongs to (e.g. `hr`).
     * @param  string  $labelKey  Translation key for the form label shown in the picker / task header.
     * @param  array<string, mixed>  $rendering  `['mode' => 'core_schema']` or
     *                                           `['mode' => 'slot_widget', 'slot' => 'process.user_task.form', 'component' => '<app>.<Component>', 'slot_api_version' => '1.0']`.
     * @param  array<string, mixed>  $schema  `['fields' => [{ key, type, required?, label?, label_key?, enum?, enum_labels? }]]`.
     *                                        App-owned fields should provide `label_key`; enum fields should map every raw enum member to an i18n key through `enum_labels`. Inline Core-authored fields may use a plain `label`.
     * @param  array<string, mixed>  $outputSchema  `{ fieldKey: { type, variable?, required?, nullable?, enum?, label_key?, enum_labels? } }` — the typed output shape declared for downstream IO. Optional `variable` is the canonical process-variable path (without the `variables.` prefix) the field routes to; when set, the editor maps the form output straight to it (e.g. `job_posting.posting_title`) so a downstream action reading that variable is satisfied. A required non-null string/enum output may declare its closed `enum` values and translated labels so the editor can safely offer it as a downstream gateway source. Absent `variable` → the editor uses the generic `task_results.<task>.fields.<key>` location.
     * @param  list<string>  $allowedResourceActions  Resource-action keys the form may invoke (resource create/update intent).
     * @param  string|null  $submissionActionKey  App action invoked atomically when a slot widget completes the task.
     * @param  string  $version  Descriptor revision (doctrine 12 metadata).
     * @param  DescriptorStatus  $status  Descriptor lifecycle status (independent of process/decision lifecycle).
     */
    public function __construct(
        public readonly string $key,
        public readonly string $appKey,
        public readonly string $labelKey,
        public readonly array $rendering = ['mode' => 'core_schema'],
        public readonly array $schema = ['fields' => []],
        public readonly array $outputSchema = [],
        public readonly array $allowedResourceActions = [],
        public readonly string $version = '1.0',
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly ?string $submissionActionKey = null,
    ) {
        if (trim($key) === '') {
            throw new \InvalidArgumentException(
                'ProcessUserTaskFormDescriptor key must be a non-empty string.',
            );
        }
        if (trim($appKey) === '') {
            throw new \InvalidArgumentException(
                "ProcessUserTaskFormDescriptor [{$key}] app_key must be a non-empty string.",
            );
        }
        // The descriptor `key` is the resolver key consumed by
        // InstalledAppResolver::filterDescriptors — its first dot segment
        // is matched against the tenant's installed apps. A descriptor must
        // not claim mismatched ownership: the key has to be prefixed by its
        // own `{appKey}.` so the resolved app and the declared app agree.
        if (! str_starts_with($key, $appKey.'.')) {
            throw new \InvalidArgumentException(
                "ProcessUserTaskFormDescriptor [{$key}] key must start with the app key prefix [{$appKey}.].",
            );
        }
        if (trim($labelKey) === '') {
            throw new \InvalidArgumentException(
                "ProcessUserTaskFormDescriptor [{$key}] label_key must be a non-empty string.",
            );
        }
        if (trim($version) === '') {
            throw new \InvalidArgumentException(
                "ProcessUserTaskFormDescriptor [{$key}] version must be a non-empty string.",
            );
        }

        $mode = $rendering['mode'] ?? null;
        if (! is_string($mode) || ! in_array($mode, self::RENDERING_MODES, true)) {
            throw new \InvalidArgumentException(
                "ProcessUserTaskFormDescriptor [{$key}] rendering.mode must be one of: "
                .implode(', ', self::RENDERING_MODES),
            );
        }
        if ($mode === 'slot_widget') {
            $slot = $rendering['slot'] ?? null;
            $component = $rendering['component'] ?? null;
            if (! is_string($slot) || $slot === '' || ! is_string($component) || $component === '') {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] slot_widget rendering requires non-empty [slot] and [component].",
                );
            }
            if ($slot !== self::FORM_SLOT) {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] slot_widget rendering requires slot [".self::FORM_SLOT.'].',
                );
            }
            $slotApiVersion = $rendering['slot_api_version'] ?? null;
            if (! in_array($slotApiVersion, [1, '1', '1.0'], true)) {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] slot_widget rendering supports only slot_api_version [1].",
                );
            }
            if (! is_string($submissionActionKey) || trim($submissionActionKey) === '') {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] slot_widget rendering requires a non-empty submission_action_key.",
                );
            }
        } elseif ($submissionActionKey !== null) {
            throw new \InvalidArgumentException(
                "ProcessUserTaskFormDescriptor [{$key}] submission_action_key is supported only for slot_widget rendering.",
            );
        }

        foreach ($outputSchema as $outputKey => $output) {
            if (! is_string($outputKey)
                || trim($outputKey) === ''
                || preg_match('/[.*\[\]]/', $outputKey) === 1) {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] output keys must be non-empty path-safe strings.",
                );
            }
            if (! is_array($output)) {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] output [{$outputKey}] must be an object.",
                );
            }
            $outputType = $output['type'] ?? null;
            if (! is_string($outputType) || ! in_array($outputType, self::SUPPORTED_OUTPUT_TYPES, true)) {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] output [{$outputKey}] type must be one of: "
                    .implode(', ', self::SUPPORTED_OUTPUT_TYPES).'.',
                );
            }
            foreach (['required', 'nullable'] as $flag) {
                if (array_key_exists($flag, $output) && ! is_bool($output[$flag])) {
                    throw new \InvalidArgumentException(
                        "ProcessUserTaskFormDescriptor [{$key}] output [{$outputKey}] {$flag} must be boolean.",
                    );
                }
            }
            if (array_key_exists('label_key', $output)
                && (! is_string($output['label_key']) || trim($output['label_key']) === '')) {
                throw new \InvalidArgumentException(
                    "ProcessUserTaskFormDescriptor [{$key}] output [{$outputKey}] label_key must be a non-empty string.",
                );
            }
            if (array_key_exists('enum', $output)) {
                $enum = $output['enum'];
                if (! in_array($outputType, ['string', 'enum'], true)
                    || ! is_array($enum)
                    || ! array_is_list($enum)
                    || $enum === []
                    || count(array_filter($enum, static fn ($value): bool => is_string($value) && trim($value) !== '')) !== count($enum)
                    || count(array_unique($enum)) !== count($enum)) {
                    throw new \InvalidArgumentException(
                        "ProcessUserTaskFormDescriptor [{$key}] output [{$outputKey}] enum must be a non-empty unique string list on a string/enum output.",
                    );
                }
            }
            if (array_key_exists('enum_labels', $output)) {
                $enum = $output['enum'] ?? null;
                $labels = $output['enum_labels'];
                if (! is_array($enum)
                    || ! is_array($labels)
                    || array_diff($enum, array_keys($labels)) !== []
                    || count(array_filter($labels, static fn ($value): bool => is_string($value) && trim($value) !== '')) !== count($labels)) {
                    throw new \InvalidArgumentException(
                        "ProcessUserTaskFormDescriptor [{$key}] output [{$outputKey}] enum_labels must map every enum value to a non-empty translation key.",
                    );
                }
            }
        }
    }

    /**
     * Declared form fields, normalized to a list of arrays.
     *
     * @return list<array<string, mixed>>
     */
    public function fields(): array
    {
        $fields = $this->schema['fields'] ?? null;
        if (! is_array($fields)) {
            return [];
        }

        return array_values(array_filter($fields, 'is_array'));
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
