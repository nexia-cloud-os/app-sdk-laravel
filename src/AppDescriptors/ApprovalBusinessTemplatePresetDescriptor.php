<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;
use InvalidArgumentException;
use Nexia\Approval\Domain\Enums\ApprovalDocumentEditorMode;

/** Copyable App-owned starting point for a tenant business form template. */
final readonly class ApprovalBusinessTemplatePresetDescriptor implements AppDescriptor
{
    public readonly string $key;

    public function __construct(
        public string $appKey,
        public string $presetKey,
        public string $bindingKey,
        public string $nameKey,
        public ?string $descriptionKey = null,
        public ApprovalDocumentEditorMode $documentEditorMode = ApprovalDocumentEditorMode::None,
        public ?string $bodyTemplate = null,
        public bool $rejectRequiresComment = true,
        public bool $requiresStrongSignature = false,
        public string $version = '1.0',
        public DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        foreach ([
            'appKey' => $appKey,
            'presetKey' => $presetKey,
            'bindingKey' => $bindingKey,
            'nameKey' => $nameKey,
            'version' => $version,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Approval business template preset {$field} must be non-blank and normalized.");
            }
        }
        if ($descriptionKey !== null && ($descriptionKey === '' || $descriptionKey !== trim($descriptionKey))) {
            throw new InvalidArgumentException('Approval business template preset descriptionKey must be null or normalized.');
        }
        if ($documentEditorMode->isRequired() && trim((string) $bodyTemplate) === '') {
            throw new InvalidArgumentException('A required approval document editor preset must provide a body template.');
        }

        $this->key = $appKey.'.'.$presetKey;
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
