<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\Approval\Domain\ApprovalRoutePolicyStep;

/** Copyable App-owned starting point for a tenant route-policy draft. */
final readonly class ApprovalRoutePolicyPresetDescriptor implements AppDescriptor
{
    public readonly string $key;

    /** @param list<ApprovalRoutePolicyStep> $steps */
    public function __construct(
        public string $appKey,
        public string $presetKey,
        public string $nameKey,
        public array $steps,
        public ?string $descriptionKey = null,
        public string $version = '1.0',
        public DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        foreach ([
            'appKey' => $appKey,
            'presetKey' => $presetKey,
            'nameKey' => $nameKey,
            'version' => $version,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Approval route-policy preset {$field} must be non-blank and normalized.");
            }
        }
        if ($steps === []) {
            throw new InvalidArgumentException('Approval route-policy preset must contain at least one resolver step.');
        }
        foreach ($steps as $step) {
            if (! $step instanceof ApprovalRoutePolicyStep || trim($step->resolverType) === '') {
                throw new InvalidArgumentException('Approval route-policy preset steps must be valid resolver-step objects.');
            }
        }
        if ($descriptionKey !== null && ($descriptionKey === '' || $descriptionKey !== trim($descriptionKey))) {
            throw new InvalidArgumentException('Approval route-policy preset descriptionKey must be null or normalized.');
        }

        $this->key = $appKey.'.'.$presetKey;
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
