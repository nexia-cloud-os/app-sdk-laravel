<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

final readonly class SignatureCapability
{
    public function __construct(
        public SignatureCapabilityKind $kind,
        public string $key,
        public SignatureCapabilityStatus $status,
        public string $labelKey,
        public string $descriptionKey,
        public ?SignatureCapabilityUnavailableReason $unavailableReason = null,
    ) {
        foreach (['key' => $key, 'labelKey' => $labelKey, 'descriptionKey' => $descriptionKey] as $name => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature capability {$name} must be a normalized non-blank string.");
            }
        }

        if (($status === SignatureCapabilityStatus::Operational) !== ($unavailableReason === null)) {
            throw new InvalidArgumentException('Operational signature capabilities cannot have an unavailable reason, and unavailable capabilities require one.');
        }
    }

    /** @return array{kind: string, key: string, status: string, label_key: string, description_key: string, reason_code: string|null} */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'key' => $this->key,
            'status' => $this->status->value,
            'label_key' => $this->labelKey,
            'description_key' => $this->descriptionKey,
            'reason_code' => $this->unavailableReason?->value,
        ];
    }

    /** @param array{kind: string, key: string, status: string, label_key: string, description_key: string, reason_code?: string|null} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            kind: SignatureCapabilityKind::from($data['kind']),
            key: $data['key'],
            status: SignatureCapabilityStatus::from($data['status']),
            labelKey: $data['label_key'],
            descriptionKey: $data['description_key'],
            unavailableReason: isset($data['reason_code'])
                ? SignatureCapabilityUnavailableReason::from($data['reason_code'])
                : null,
        );
    }
}
