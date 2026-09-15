<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * App-contributed non-user process work action.
 *
 * A `ProcessWorkActionDescriptor` declares a single process-runtime
 * action that the BPMN authoring surface can pick from a guided
 * catalog instead of asking the user to type a raw worker topic. The
 * descriptor carries the runtime topic, the payload schema the
 * validator enforces at publish, and the set of binding sources the
 * payload may pull from.
 *
 * Apps cannot introduce new `kind` values — the Core fixed set is
 * `serviceTask`, `sendTask`, `receiveTask`. The descriptor is the only
 * legitimate source of worker / message topics on the authoring
 * surface.
 *
 * @see docs/reference/APP-DESCRIPTORS.md — current Descriptor Categories
 * @see docs/decisions/ARD-20260528-process-work-action-descriptor.md
 */
final class ProcessWorkActionDescriptor implements AppDescriptor
{
    public const SUPPORTED_KINDS = ['serviceTask', 'sendTask', 'receiveTask'];

    /**
     * @param  string  $kind  One of SUPPORTED_KINDS.
     * @param  string  $appKey  App key the action belongs to (e.g. 'hr').
     * @param  string  $actionKey  Stable action identifier within the app (e.g. 'leave_request.finalize').
     * @param  string  $version  Descriptor revision (doctrine 12 metadata).
     * @param  string  $labelKey  Translation key for the action label shown in the BPMN picker.
     * @param  string  $topic  Worker topic (for serviceTask) or message topic (for sendTask / receiveTask).
     * @param  array<string, mixed>  $payloadSchema  Per-key validation + authoring map. Each entry:
     *                                               `{ type?, enum?, required?, label_key?, help_key?, enum_labels? }`.
     *                                               `type`/`enum`/`required` are publish-time validation (U06.6). The
     *                                               authoring metadata (U06.3) is display-only and never reaches the
     *                                               persisted payload: `label_key` names the field label shown in
     *                                               the BPMN payload editor, `help_key` an optional inline help
     *                                               string, and `enum_labels` maps each raw enum value to an i18n key
     *                                               so the picker shows friendly labels instead of raw values
     *                                               (e.g. `approved` → `sample:work_actions.request_finalize.target_status.approved`).
     *                                               New descriptors should declare label metadata; legacy descriptors
     *                                               without it render a generic business label rather than leaking
     *                                               raw key/value identifiers.
     * @param  list<string>  $allowedBindingSources  Permitted source paths for
     *                                               non-enum payload source binding. Enum fields are bound to a UserTask
     *                                               outcome through an Activity IO dataInputAssociation carrying a
     *                                               `nexia.valueMap` (ARD-20260707), gated by enum compatibility
     *                                               rather than this list.
     * @param  list<array<string, mixed>>  $inputContract  Declared required process-variable
     *                                                     inputs the worker reads from the instance
     *                                                     variable bag (distinct from `payloadSchema`,
     *                                                     which validates literal/bound payload keys).
     *                                                     Each entry: `{ path: 'variables.<...>', type?, required?, nullable? }`.
     *                                                     `path` must be a non-empty string under the
     *                                                     `variables.` namespace; `required` defaults to
     *                                                     true; `nullable` defaults to false. The
     *                                                     publish-time validator requires every required
     *                                                     input to have a guaranteed upstream producer that
     *                                                     dominates the work-action element, so the value
     *                                                     cannot be missing at runtime.
     * @param  DescriptorStatus  $status  Lifecycle status of the descriptor itself (independent of process/decision lifecycle).
     * @param  ProcessApprovalTaskConfiguration|null  $approvalTask  Long-running Approval Task semantics.
     * @param  array<string, mixed>  $outputContract  Stable output fields materialized by the action.
     */
    /**
     * Composite descriptor key used by host installed-app resolution. Format:
     * `{appKey}.{actionKey}`. The first dot-separated segment is the app key,
     * which the host matches against the tenant's active app installations.
     */
    public readonly string $key;

    public function __construct(
        public readonly string $kind,
        public readonly string $appKey,
        public readonly string $actionKey,
        public readonly string $labelKey,
        public readonly string $topic,
        public readonly string $version = '1.0',
        public readonly array $payloadSchema = [],
        public readonly array $allowedBindingSources = [],
        public readonly array $inputContract = [],
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly ?ProcessApprovalTaskConfiguration $approvalTask = null,
        public readonly array $outputContract = [],
    ) {
        if (! in_array($kind, self::SUPPORTED_KINDS, true)) {
            throw new \InvalidArgumentException(
                "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] kind [{$kind}] must be one of: "
                .implode(', ', self::SUPPORTED_KINDS),
            );
        }
        if (trim($appKey) === '') {
            throw new \InvalidArgumentException(
                'ProcessWorkActionDescriptor app_key must be a non-empty string.',
            );
        }
        if (trim($actionKey) === '') {
            throw new \InvalidArgumentException(
                "ProcessWorkActionDescriptor [{$appKey}] action_key must be a non-empty string.",
            );
        }
        if (trim($labelKey) === '') {
            throw new \InvalidArgumentException(
                "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] label_key must be a non-empty string.",
            );
        }
        if (trim($topic) === '') {
            throw new \InvalidArgumentException(
                "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] topic must be a non-empty string.",
            );
        }
        if (trim($version) === '') {
            throw new \InvalidArgumentException(
                "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] version must be a non-empty string.",
            );
        }
        if ($approvalTask !== null && $kind !== 'serviceTask') {
            throw new \InvalidArgumentException(
                "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] Approval Task must use kind [serviceTask].",
            );
        }
        if ($approvalTask !== null) {
            foreach ([
                $approvalTask->templatePayloadKey => 'approval.business_template',
                $approvalTask->routePolicyPayloadKey => 'approval.route_policy',
            ] as $payloadKey => $catalogRef) {
                $field = $payloadSchema[$payloadKey] ?? null;
                if (! is_array($field) || ($field['catalog_ref'] ?? null) !== $catalogRef) {
                    throw new \InvalidArgumentException(
                        "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] Approval Task payload [{$payloadKey}] must reference catalog [{$catalogRef}].",
                    );
                }
            }
            $templateFilter = $payloadSchema[$approvalTask->templatePayloadKey]['filter'] ?? null;
            if (! is_array($templateFilter)
                || ($templateFilter['binding_key'] ?? null) !== $approvalTask->bindingKey) {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] Approval Task template catalog must filter binding [{$approvalTask->bindingKey}].",
                );
            }
            $routeFilter = $payloadSchema[$approvalTask->routePolicyPayloadKey]['filter'] ?? null;
            if (! is_array($routeFilter) || ($routeFilter['status'] ?? null) !== 'active') {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] Approval Task route-policy catalog must filter active policies.",
                );
            }
        }
        foreach ($inputContract as $index => $entry) {
            if (! is_array($entry)) {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] input_contract[{$index}] must be an object.",
                );
            }
            $path = $entry['path'] ?? null;
            if (! is_string($path) || trim($path) === '') {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] input_contract[{$index}] requires a non-empty [path].",
                );
            }
            // The input path namespace encodes its source kind (ARD-20260619):
            // `variables.` is produced by an upstream node (userTask /
            // BusinessRuleTask); `event_payload.` is carried by the start
            // trigger; `resource.` is a field of the instance's anchored
            // resource. Publish validation (ProcessDefinitionValidator) enforces
            // that each declared path is actually guaranteed for the graph.
            $trimmedPath = trim($path);
            if (
                ! str_starts_with($trimmedPath, 'variables.')
                && ! str_starts_with($trimmedPath, 'event_payload.')
                && ! str_starts_with($trimmedPath, 'resource.')
            ) {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] input_contract[{$index}] path [{$path}] must address the [variables.], [event_payload.], or [resource.] namespace.",
                );
            }
            if (array_key_exists('required', $entry) && ! is_bool($entry['required'])) {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] input_contract[{$index}] required must be a boolean when present.",
                );
            }
            if (array_key_exists('nullable', $entry) && ! is_bool($entry['nullable'])) {
                throw new \InvalidArgumentException(
                    "ProcessWorkActionDescriptor [{$appKey}.{$actionKey}] input_contract[{$index}] nullable must be a boolean when present.",
                );
            }
        }
        $this->key = trim($appKey).'.'.trim($actionKey);
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
