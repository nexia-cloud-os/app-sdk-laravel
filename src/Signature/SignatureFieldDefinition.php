<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

final readonly class SignatureFieldDefinition
{
    public function __construct(
        public string $key,
        public string $signatoryRoleKey,
        public SignatureFieldKind $kind,
        public SignatureFieldRect $rect,
        public bool $required = true,
        public ?int $participantSlot = null,
        public ?string $label = null,
        public ?string $inputGuide = null,
        public string|bool|null $defaultValue = null,
        public array $options = [],
        public ?SignatureTemporalDisplayPart $datePart = null,
    ) {
        foreach (['key' => $key, 'signatoryRoleKey' => $signatoryRoleKey] as $name => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature field {$name} must be a normalized non-blank string.");
            }
        }

        if ($participantSlot !== null && $participantSlot < 1) {
            throw new InvalidArgumentException('Signature field participant slot must be a positive integer when present.');
        }

        foreach (['label' => $label, 'inputGuide' => $inputGuide] as $name => $value) {
            if ($value !== null && ($value === '' || $value !== trim($value))) {
                throw new InvalidArgumentException("Signature field {$name} must be null or a normalized non-blank string.");
            }
        }
        if (! array_is_list($options)) {
            throw new InvalidArgumentException('Signature field options must be a list.');
        }
        foreach ($options as $option) {
            if (! is_string($option) || $option === '' || $option !== trim($option)) {
                throw new InvalidArgumentException('Signature field options must contain normalized non-blank strings.');
            }
        }
        $allowedDateParts = match ($kind) {
            SignatureFieldKind::Date => [
                SignatureTemporalDisplayPart::Year,
                SignatureTemporalDisplayPart::Month,
                SignatureTemporalDisplayPart::Day,
            ],
            SignatureFieldKind::SignedAt => SignatureTemporalDisplayPart::cases(),
            default => [],
        };
        if ($datePart !== null && ! in_array($datePart, $allowedDateParts, true)) {
            throw new InvalidArgumentException("Signature field kind [{$kind->value}] does not support date part [{$datePart->value}].");
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'key' => $this->key,
            'signatory_role_key' => $this->signatoryRoleKey,
            'kind' => $this->kind->value,
            'rect' => $this->rect->toArray(),
            'required' => $this->required,
        ];
        if ($this->participantSlot !== null) {
            $data['participant_slot'] = $this->participantSlot;
        }
        if ($this->label !== null) {
            $data['label'] = $this->label;
        }
        if ($this->inputGuide !== null) {
            $data['input_guide'] = $this->inputGuide;
        }
        if ($this->defaultValue !== null) {
            $data['default_value'] = $this->defaultValue;
        }
        if ($this->options !== []) {
            $data['options'] = $this->options;
        }
        if ($this->datePart !== null) {
            $data['date_part'] = $this->datePart->value;
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $participantSlot = $data['participant_slot'] ?? null;
        if ($participantSlot !== null && (! is_int($participantSlot) || $participantSlot < 1)) {
            throw new InvalidArgumentException('Signature field serialized participant slot must be a positive integer when present.');
        }
        $options = $data['options'] ?? [];
        $defaultValue = $data['default_value'] ?? null;
        $label = $data['label'] ?? null;
        $inputGuide = $data['input_guide'] ?? null;
        $datePart = isset($data['date_part'])
            ? SignatureTemporalDisplayPart::tryFrom(is_string($data['date_part']) ? $data['date_part'] : '')
            : null;
        if (! is_array($options)
            || ! array_is_list($options)
            || (! is_string($defaultValue) && ! is_bool($defaultValue) && $defaultValue !== null)
            || (! is_string($label) && $label !== null)
            || (! is_string($inputGuide) && $inputGuide !== null)) {
            throw new InvalidArgumentException('Signature field serialized configuration is invalid.');
        }
        if (array_key_exists('date_part', $data) && ! $datePart instanceof SignatureTemporalDisplayPart) {
            throw new InvalidArgumentException('Signature field serialized date part is invalid.');
        }

        return new self(
            key: $data['key'],
            signatoryRoleKey: $data['signatory_role_key'],
            kind: SignatureFieldKind::from($data['kind']),
            rect: SignatureFieldRect::fromArray($data['rect']),
            required: $data['required'] ?? true,
            participantSlot: $participantSlot,
            label: $label,
            inputGuide: $inputGuide,
            defaultValue: $defaultValue,
            options: $options,
            datePart: $datePart,
        );
    }
}
