<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\AppDescriptors\Contracts\AppDescriptor;
use Nexia\Approval\Domain\ApprovalRoutePolicyStep;
use Nexia\Approval\Domain\Enums\ApprovalDocumentEditorMode;

/** Data-only platform declarations. Wire input never selects a PHP class. */
final class PlatformDescriptorWire
{
    public const TYPES = [
        'process_start' => ProcessStartBindingDescriptor::class,
        'process_template' => ProcessTemplateDescriptor::class,
        'approval_binding' => ApprovalFormBindingDescriptor::class,
        'approval_document' => ApprovalDocumentSchema::class,
        'approval_template' => ApprovalBusinessTemplatePresetDescriptor::class,
        'approval_route' => ApprovalRoutePolicyPresetDescriptor::class,
        'signature_template' => SignatureTemplateBindingDescriptor::class,
    ];

    public static function encode(AppDescriptor $descriptor): array
    {
        $type = array_search($descriptor::class, self::TYPES, true);
        if ($type === false) throw new InvalidArgumentException('Unsupported platform descriptor.');
        if ($descriptor instanceof SignatureTemplateBindingDescriptor) {
            return ['type' => $type, 'data' => $descriptor->toArray()];
        }
        $data = get_object_vars($descriptor);
        if (! $descriptor instanceof ProcessTemplateDescriptor) unset($data['key']);
        $data['status'] = $descriptor->status->value;
        if ($descriptor instanceof ApprovalBusinessTemplatePresetDescriptor) {
            $data['documentEditorMode'] = $descriptor->documentEditorMode->value;
        }
        if ($descriptor instanceof ApprovalRoutePolicyPresetDescriptor) {
            $data['steps'] = array_map(static fn ($step) => $step->toArray(), $descriptor->steps);
        }
        return ['type' => $type, 'data' => $data];
    }

    public static function decode(array $wire, string $appKey): AppDescriptor
    {
        if (array_diff(array_keys($wire), ['type', 'data']) !== [] || ! is_string($wire['type'] ?? null)
            || ! isset(self::TYPES[$wire['type']]) || ! is_array($wire['data'] ?? null)) {
            throw new InvalidArgumentException('Invalid platform descriptor envelope.');
        }
        $data = $wire['data'];
        if ($wire['type'] === 'signature_template') {
            $descriptor = SignatureTemplateBindingDescriptor::fromArray($data);
        } else {
            $data['status'] = DescriptorStatus::from($data['status']);
            if ($wire['type'] === 'approval_template') {
                $data['documentEditorMode'] = ApprovalDocumentEditorMode::from($data['documentEditorMode']);
            }
            if ($wire['type'] === 'approval_route') {
                $data['steps'] = array_map(ApprovalRoutePolicyStep::fromArray(...), $data['steps']);
            }
            $class = self::TYPES[$wire['type']];
            $descriptor = new $class(...$data);
        }
        if ($descriptor->appKey !== $appKey || ! str_starts_with($descriptor->descriptorKey(), $appKey.'.')
            || self::encode($descriptor) != $wire) {
            throw new InvalidArgumentException('Platform descriptor ownership or canonical shape differs.');
        }
        return $descriptor;
    }
}
