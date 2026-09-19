<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;

/** App-owned business semantics for one long-running Process Approval Task. */
final readonly class ProcessApprovalTaskConfiguration
{
    /**
     * @param list<string> $outcomes
     */
    public function __construct(
        public string $bindingKey,
        public string $outcomeTopic,
        public array $outcomes = ['approved', 'rejected', 'recalled', 'cancelled'],
        public string $templatePayloadKey = 'template_key',
        public string $routePolicyPayloadKey = 'approval_route_policy_key',
        public ?string $businessTemplatePresetKey = null,
        public ?string $routePolicyPresetKey = null,
        public bool $supportsLinePreparation = false,
    ) {
        foreach ([
            'bindingKey' => $bindingKey,
            'outcomeTopic' => $outcomeTopic,
            'templatePayloadKey' => $templatePayloadKey,
            'routePolicyPayloadKey' => $routePolicyPayloadKey,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Process Approval Task {$field} must be non-blank and normalized.");
            }
        }

        if ($outcomes === [] || count(array_unique($outcomes)) !== count($outcomes)) {
            throw new InvalidArgumentException('Process Approval Task outcomes must be a non-empty unique list.');
        }
        foreach ($outcomes as $outcome) {
            if (! is_string($outcome) || $outcome === '' || $outcome !== trim($outcome)) {
                throw new InvalidArgumentException('Process Approval Task outcomes must be normalized strings.');
            }
        }

        foreach ([
            'businessTemplatePresetKey' => $businessTemplatePresetKey,
            'routePolicyPresetKey' => $routePolicyPresetKey,
        ] as $field => $value) {
            if ($value !== null && ($value === '' || $value !== trim($value))) {
                throw new InvalidArgumentException("Process Approval Task {$field} must be null or a normalized non-blank string.");
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'binding_key' => $this->bindingKey,
            'outcome_topic' => $this->outcomeTopic,
            'outcomes' => $this->outcomes,
            'template_payload_key' => $this->templatePayloadKey,
            'route_policy_payload_key' => $this->routePolicyPayloadKey,
            'business_template_preset_key' => $this->businessTemplatePresetKey,
            'route_policy_preset_key' => $this->routePolicyPresetKey,
            'supports_line_preparation' => $this->supportsLinePreparation,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
