<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;

/**
 * Catalog entry for a BPMN/DMN process template.
 *
 * A process template is a creation-time authoring convenience, NOT a
 * runtime element: applying it copies the canonical BPMN `structure` into an
 * editable host draft. The package template descriptor itself is never
 * mutated and is never the runtime definition.
 *
 * `decisions` is retained as legacy recommendation metadata only. Core must
 * never create DMN records from it. Apps that want reusable DMN starters
 * contribute `DecisionResultTemplateDescriptor` entries, and the author
 * explicitly chooses and saves one in the DMN editor.
 *
 * Core ships app-neutral reference templates (`category: 'core'`,
 * `appKey: 'core'` / null, no dependencies). Apps may contribute
 * templates (`category: 'app'`) that declare `dependencies` on stable
 * descriptors (resources / actions / forms / events / workers / messages
 * / decisions). The template catalog resolver
 * hides app templates whose owning app is not installed and reports any
 * missing dependency BEFORE creation; missing app descriptors never break
 * host template listing or creation.
 *
 * Template contribution is optional — Core works with no app package
 * installed. This is a closed descriptor category: apps contribute
 * entries but cannot introduce new template `category` or dependency
 * `kind` values.
 *
 * @see docs/reference/APP-DESCRIPTORS.md — current Descriptor Categories
 * @see docs/reference/APP-DESCRIPTORS.md — Process Template Descriptor
 */
final class ProcessTemplateDescriptor implements AppDescriptor
{
    /** A template is Core-owned (app-neutral) or app-contributed. */
    public const CATEGORY_CORE = 'core';

    public const CATEGORY_APP = 'app';

    /** @var list<string> */
    public const SUPPORTED_CATEGORIES = [self::CATEGORY_CORE, self::CATEGORY_APP];

    /**
     * Closed set of dependency kinds a template may declare. Each
     * dependency is resolved against the tenant's currently installed app
     * descriptor catalog before the template can be installed.
     *
     * @var list<string>
     */
    public const SUPPORTED_DEPENDENCY_KINDS = [
        'resource',
        'action',
        'form',
        'event',
        'worker',
        'message',
        'decision',
    ];

    /**
     * @param  string  $key  Composite catalog key (e.g. `core.basic_approval`,
     *                       `sample.process`). Stable template identifier. The first dot-separated
     *                       segment is the owning app key used by
     *                       host installed-app resolution for tenant visibility
     *                       (`core.*` is always visible).
     * @param  string  $version  Descriptor revision (doctrine 12 metadata).
     * @param  string|null  $appKey  App key the template belongs to. `null` or
     *                               `'core'` marks an app-neutral Core template.
     * @param  string  $labelKey  Translation key for the catalog label.
     * @param  string  $category  One of SUPPORTED_CATEGORIES.
     * @param  array<string, mixed>  $structure  Canonical BPMN `definitions`
     *                                           document copied verbatim into a
     *                                           new editable ProcessDefinition.
     * @param  list<array<string, mixed>>  $decisions  Legacy DMN authoring hints.
     *                                                 Never persisted automatically.
     * @param  list<array{kind: string, key: string, version?: string}>  $dependencies
     *                                                                                  Descriptor refs the template
     *                                                                                  requires. Resolved against the
     *                                                                                  installed catalog before install.
     * @param  DescriptorStatus  $status  Lifecycle status of the descriptor.
     * @param  string|null  $descriptionKey  Translation key for a one-line
     *                                       catalog description shown beside
     *                                       the label in the template chooser.
     *                                       `null` renders the label alone.
     * @param  array{definitions?: string, processes?: array<string, string>, elements?: array<string, string>}  $nameKeys
     *                                                                                                                      Translation keys for user-visible
     *                                                                                                                      BPMN names, indexed by process or
     *                                                                                                                      globally unique element id.
     * @param  string|null  $startResourceKey  Resource that a manually started
     *                                         App process must anchor to. Event
     *                                         and call-activity starts supply
     *                                         their own subject instead.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly ?string $appKey,
        public readonly string $labelKey,
        public readonly string $category,
        public readonly array $structure,
        public readonly array $decisions = [],
        public readonly array $dependencies = [],
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly ?string $descriptionKey = null,
        public readonly array $nameKeys = [],
        /** Install one editable definition per Legal Entity; never overwrite an existing definition. */
        public readonly bool $installByDefault = false,
        public readonly ?string $startResourceKey = null,
    ) {
        if (trim($key) === '') {
            throw new \InvalidArgumentException('ProcessTemplateDescriptor key must be a non-empty string.');
        }
        if ($appKey !== null) {
            if (trim($appKey) === '') {
                throw new \InvalidArgumentException("ProcessTemplateDescriptor [{$key}] app_key must be a non-empty string when provided.");
            }

            $ownerKey = explode('.', $key, 2)[0];
            if ($ownerKey !== $appKey) {
                throw new \InvalidArgumentException(
                    "ProcessTemplateDescriptor [{$key}] app_key [{$appKey}] must match key owner [{$ownerKey}].",
                );
            }
        }
        if (trim($version) === '') {
            throw new \InvalidArgumentException("ProcessTemplateDescriptor [{$key}] version must be a non-empty string.");
        }
        if (trim($labelKey) === '') {
            throw new \InvalidArgumentException("ProcessTemplateDescriptor [{$key}] label_key must be a non-empty string.");
        }
        if (! in_array($category, self::SUPPORTED_CATEGORIES, true)) {
            throw new \InvalidArgumentException(
                "ProcessTemplateDescriptor [{$key}] category [{$category}] must be one of: "
                .implode(', ', self::SUPPORTED_CATEGORIES),
            );
        }
        if (! is_array($structure['definitions'] ?? null)) {
            throw new \InvalidArgumentException(
                "ProcessTemplateDescriptor [{$key}] structure must be a canonical BPMN definitions document.",
            );
        }
        if ($startResourceKey !== null && (
            trim($startResourceKey) === ''
            || ! array_any($dependencies, static fn (mixed $dependency): bool =>
                is_array($dependency)
                && ($dependency['kind'] ?? null) === 'resource'
                && ($dependency['key'] ?? null) === $startResourceKey
            )
        )) {
            throw new \InvalidArgumentException(
                "ProcessTemplateDescriptor [{$key}] start_resource_key must name a declared resource dependency.",
            );
        }

        foreach ($decisions as $index => $decision) {
            if (! is_array($decision)
                || ! is_string($decision['key'] ?? null)
                || trim((string) $decision['key']) === ''
                || ! is_array($decision['decisionTable'] ?? null)
            ) {
                throw new \InvalidArgumentException(
                    "ProcessTemplateDescriptor [{$key}] decisions[{$index}] requires a non-empty key and a decisionTable array.",
                );
            }
        }

        foreach ($dependencies as $index => $dependency) {
            if (! is_array($dependency)
                || ! is_string($dependency['kind'] ?? null)
                || ! in_array($dependency['kind'], self::SUPPORTED_DEPENDENCY_KINDS, true)
            ) {
                throw new \InvalidArgumentException(
                    "ProcessTemplateDescriptor [{$key}] dependencies[{$index}] kind must be one of: "
                    .implode(', ', self::SUPPORTED_DEPENDENCY_KINDS),
                );
            }
            if (! is_string($dependency['key'] ?? null) || trim((string) $dependency['key']) === '') {
                throw new \InvalidArgumentException(
                    "ProcessTemplateDescriptor [{$key}] dependencies[{$index}] requires a non-empty key.",
                );
            }
        }

        $this->validateNameKeys($structure, $nameKeys);
    }

    /**
     * @param  array<string, mixed>  $structure
     * @param  array{definitions?: string, processes?: array<string, string>, elements?: array<string, string>}  $nameKeys
     */
    private function validateNameKeys(array $structure, array $nameKeys): void
    {
        $allowedSections = ['definitions', 'processes', 'elements'];
        if (array_diff(array_keys($nameKeys), $allowedSections) !== []) {
            throw new \InvalidArgumentException(
                "ProcessTemplateDescriptor [{$this->key}] name_keys contains an unsupported section.",
            );
        }

        if (array_key_exists('definitions', $nameKeys)
            && (! is_string($nameKeys['definitions']) || trim($nameKeys['definitions']) === '')) {
            throw new \InvalidArgumentException(
                "ProcessTemplateDescriptor [{$this->key}] definitions name_key must be a non-empty string.",
            );
        }

        $processes = $structure['definitions']['processes'] ?? [];
        $processIds = [];
        foreach (is_array($processes) ? $processes : [] as $process) {
            if (is_array($process) && is_string($process['id'] ?? null) && $process['id'] !== '') {
                $processIds[] = $process['id'];
            }
        }
        $elementIds = [];
        $collectElementIds = static function (array $elements) use (&$collectElementIds, &$elementIds): void {
            foreach ($elements as $element) {
                if (! is_array($element)) {
                    continue;
                }
                if (is_string($element['id'] ?? null) && $element['id'] !== '') {
                    $elementIds[] = $element['id'];
                }
                if (is_array($element['flowElements'] ?? null)) {
                    $collectElementIds($element['flowElements']);
                }
            }
        };
        foreach (is_array($processes) ? $processes : [] as $process) {
            if (is_array($process) && is_array($process['flowElements'] ?? null)) {
                $collectElementIds($process['flowElements']);
            }
        }

        foreach (['processes' => $processIds, 'elements' => $elementIds] as $section => $knownIds) {
            $mapping = $nameKeys[$section] ?? [];
            if (! is_array($mapping)) {
                throw new \InvalidArgumentException(
                    "ProcessTemplateDescriptor [{$this->key}] name_keys.{$section} must be an id-to-key map.",
                );
            }
            foreach ($mapping as $id => $translationKey) {
                if (! is_string($id)
                    || ! in_array($id, $knownIds, true)
                    || ! is_string($translationKey)
                    || trim($translationKey) === '') {
                    throw new \InvalidArgumentException(
                        "ProcessTemplateDescriptor [{$this->key}] name_keys.{$section} contains an unknown id or empty key.",
                    );
                }
            }
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
