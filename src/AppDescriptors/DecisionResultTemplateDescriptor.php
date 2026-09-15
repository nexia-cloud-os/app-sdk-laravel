<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;
use Nexia\Process\Domain\Enums\DecisionHitPolicy;

/**
 * App-contributed DMN authoring starter shape.
 *
 * A decision-result template seeds the DMN authoring surface. The minimal
 * form recommends result fields; an optional `decisionTable` can provide a
 * complete starting point (inputs, outputs, and rules).
 *
 * Templates are authoring hints only. The author may change every seeded
 * value, a BPMN definition never stores a runtime dependency on this
 * descriptor, and Core always validates the saved DMN itself. In particular,
 * selecting a BPMN template must not create or silently apply a DMN template.
 *
 * @see docs/decisions/ARD-20260527-decision-result-template-descriptor.md
 * @see docs/reference/APP-DESCRIPTORS.md — current Descriptor Categories
 */
final class DecisionResultTemplateDescriptor implements AppDescriptor
{
    /**
     * @param  string  $key  Stable template identifier (e.g. 'sample.approval_routing').
     * @param  string  $version  Descriptor revision (matches the descriptor metadata
     *                           contract in doctrine 12: every descriptor carries an independent
     *                           version that the catalog can publish or deprecate even when the
     *                           lifecycle status alone has not changed).
     * @param  string  $labelKey  Translation key for the template label shown in the picker.
     * @param  list<DecisionResultFieldDescriptor>  $fields  Recommended result fields.
     * @param  DescriptorStatus  $status  Lifecycle status.
     * @param  string|null  $descriptionKey  Optional catalog description.
     * @param  array<string, mixed>|null  $decisionTable  Optional complete DMN
     *                                                    authoring seed.
     * @param  string|null  $hitPolicy  Hit policy used with a complete seed.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly string $labelKey,
        public readonly array $fields,
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly ?string $descriptionKey = null,
        public readonly ?array $decisionTable = null,
        public readonly ?string $hitPolicy = null,
    ) {
        if (trim($key) === '' || trim($version) === '' || trim($labelKey) === '') {
            throw new \InvalidArgumentException(
                'DecisionResultTemplateDescriptor key, version, and label_key must be non-empty strings.',
            );
        }
        if ($fields === []) {
            throw new \InvalidArgumentException(
                "DecisionResultTemplateDescriptor [{$key}] must declare at least one field.",
            );
        }

        $seenIds = [];
        foreach ($fields as $index => $field) {
            if (! $field instanceof DecisionResultFieldDescriptor) {
                throw new \InvalidArgumentException(
                    "DecisionResultTemplateDescriptor [{$key}] field [{$index}] must be a DecisionResultFieldDescriptor.",
                );
            }
            if (isset($seenIds[$field->id])) {
                throw new \InvalidArgumentException(
                    "DecisionResultTemplateDescriptor [{$key}] declares duplicate field id [{$field->id}].",
                );
            }
            $seenIds[$field->id] = true;
        }

        if ($descriptionKey !== null && trim($descriptionKey) === '') {
            throw new \InvalidArgumentException(
                "DecisionResultTemplateDescriptor [{$key}] description_key must be null or a non-empty string.",
            );
        }

        if ($decisionTable !== null) {
            foreach (['inputs', 'outputs', 'rules'] as $section) {
                if (! is_array($decisionTable[$section] ?? null)) {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] decisionTable requires an array [{$section}] section.",
                    );
                }
                if (! array_is_list($decisionTable[$section])) {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] decisionTable [{$section}] must be a list.",
                    );
                }
                if ($decisionTable[$section] === []) {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] decisionTable [{$section}] must not be empty.",
                    );
                }
            }

            // Exactly one input. The guided decision editor's draft holds a
            // single input column, so a multi-input starter cannot be seeded:
            // the editor parks it and silently falls back to output columns
            // only, discarding the rules, name, and labels shipped here. Fail
            // at construction instead — a starter that arrives empty in the
            // editor gives the author no clue which descriptor produced it.
            if (count($decisionTable['inputs']) !== 1) {
                throw new \InvalidArgumentException(
                    "DecisionResultTemplateDescriptor [{$key}] decisionTable must declare exactly one input; "
                    .'the guided decision editor seeds a single input column.',
                );
            }

            $input = $decisionTable['inputs'][0];
            $inputId = is_array($input) && is_string($input['id'] ?? null)
                ? trim($input['id'])
                : '';
            if ($inputId === '') {
                throw new \InvalidArgumentException(
                    "DecisionResultTemplateDescriptor [{$key}] decisionTable input requires a non-empty id.",
                );
            }
            if (is_array($input) && array_key_exists('source', $input)) {
                $source = $input['source'];
                if (! is_array($source)) {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] decisionTable input source must be an array.",
                    );
                }

                $sourceKind = is_string($source['kind'] ?? null)
                    ? trim($source['kind'])
                    : '';
                if (! in_array($sourceKind, ['event_payload', 'process_variable'], true)) {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] decisionTable input source kind must be [event_payload] or [process_variable].",
                    );
                }

                if ($sourceKind === 'event_payload') {
                    $eventName = is_string($source['event_name'] ?? null)
                        ? trim($source['event_name'])
                        : '';
                    $payloadKey = is_string($source['payload_key'] ?? null)
                        ? trim($source['payload_key'])
                        : '';
                    $expression = is_string($input['expression'] ?? null)
                        ? trim($input['expression'])
                        : '';
                    if ($eventName === '' || $payloadKey === '') {
                        throw new \InvalidArgumentException(
                            "DecisionResultTemplateDescriptor [{$key}] event_payload input source requires event_name and payload_key.",
                        );
                    }
                    if ($expression !== 'event_payload.'.$payloadKey) {
                        throw new \InvalidArgumentException(
                            "DecisionResultTemplateDescriptor [{$key}] event_payload input expression must match its payload_key.",
                        );
                    }
                }

                if ($sourceKind === 'process_variable') {
                    $expression = is_string($input['expression'] ?? null)
                        ? trim($input['expression'])
                        : '';
                    if ($expression !== $inputId) {
                        throw new \InvalidArgumentException(
                            "DecisionResultTemplateDescriptor [{$key}] process_variable input expression must equal its input id.",
                        );
                    }
                }
            }

            if ($hitPolicy === null || DecisionHitPolicy::tryFrom(trim($hitPolicy)) === null) {
                throw new \InvalidArgumentException(
                    "DecisionResultTemplateDescriptor [{$key}] with a decisionTable requires a supported hitPolicy.",
                );
            }

            $seedOutputs = [];
            foreach ($decisionTable['outputs'] as $index => $output) {
                $outputId = is_array($output) && is_string($output['id'] ?? null)
                    ? trim($output['id'])
                    : '';
                if ($outputId === '') {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] decisionTable outputs[{$index}] requires a non-empty id.",
                    );
                }
                $seedOutputs[$outputId] = true;
            }

            foreach (array_keys($seenIds) as $fieldId) {
                if (! isset($seedOutputs[$fieldId])) {
                    throw new \InvalidArgumentException(
                        "DecisionResultTemplateDescriptor [{$key}] field [{$fieldId}] is missing from decisionTable outputs.",
                    );
                }
            }
        } elseif ($hitPolicy !== null) {
            throw new \InvalidArgumentException(
                "DecisionResultTemplateDescriptor [{$key}] cannot declare a hitPolicy without a decisionTable.",
            );
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
