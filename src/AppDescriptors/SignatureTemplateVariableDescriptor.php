<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\Signature\SignatureDataClassification;
use Nexia\Signature\SignatureVariableType;

final readonly class SignatureTemplateVariableDescriptor
{
    private const KEY_PATTERN = '/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*\z/D';

    public function __construct(
        public string $key,
        public string $labelKey,
        public SignatureVariableType $type,
        public bool $required,
        public SignatureDataClassification $sensitivity,
        public ?string $formatter = null,
    ) {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException("Signature variable key [{$key}] must be canonical lower snake-case segments.");
        }

        if ($labelKey === '' || $labelKey !== trim($labelKey)) {
            throw new InvalidArgumentException("Signature variable [{$key}] label key must be normalized and non-blank.");
        }

        if ($formatter !== null && ($formatter === '' || $formatter !== trim($formatter))) {
            throw new InvalidArgumentException("Signature variable [{$key}] formatter must be null or normalized and non-blank.");
        }
    }

    public function accepts(mixed $value): bool
    {
        if ($value === null) {
            return ! $this->required;
        }

        return match ($this->type) {
            SignatureVariableType::String,
            SignatureVariableType::Date,
            SignatureVariableType::DateTime => is_string($value),
            SignatureVariableType::Integer => is_int($value),
            SignatureVariableType::Decimal => is_int($value) || is_float($value),
            SignatureVariableType::Boolean => is_bool($value),
            SignatureVariableType::Money => is_array($value)
                && isset($value['amount'], $value['currency'])
                && (is_int($value['amount']) || is_float($value['amount']))
                && is_string($value['currency'])
                && preg_match('/\A[A-Z]{3}\z/D', $value['currency']) === 1,
        };
    }

    /** @return array{key: string, label_key: string, type: string, required: bool, sensitivity: string, formatter: string|null} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label_key' => $this->labelKey,
            'type' => $this->type->value,
            'required' => $this->required,
            'sensitivity' => $this->sensitivity->value,
            'formatter' => $this->formatter,
        ];
    }

    /** @param array{key: string, label_key: string, type: string, required: bool, sensitivity: string, formatter?: string|null} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            labelKey: $data['label_key'],
            type: SignatureVariableType::from($data['type']),
            required: $data['required'],
            sensitivity: SignatureDataClassification::from($data['sensitivity']),
            formatter: $data['formatter'] ?? null,
        );
    }
}
