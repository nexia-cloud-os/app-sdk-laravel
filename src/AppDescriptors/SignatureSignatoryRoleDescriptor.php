<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;

final readonly class SignatureSignatoryRoleDescriptor
{
    private const KEY_PATTERN = '/\A[a-z][a-z0-9_]*\z/D';

    public function __construct(
        public string $key,
        public string $labelKey,
        public int $minimum,
        public int $maximum,
        public ?SignatureParticipantAssignmentPolicy $assignmentPolicy = null,
    ) {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException("Signature signatory role key [{$key}] must be lower snake-case.");
        }

        if ($labelKey === '' || $labelKey !== trim($labelKey)) {
            throw new InvalidArgumentException("Signature signatory role [{$key}] label key must be normalized and non-blank.");
        }

        if ($minimum < 0 || $maximum < 1 || $minimum > $maximum) {
            throw new InvalidArgumentException("Signature signatory role [{$key}] cardinality must satisfy 0 <= minimum <= maximum and maximum >= 1.");
        }
    }

    /** @return array{key: string, label_key: string, minimum: int, maximum: int, assignment_policy?: array{allowed_sources:non-empty-list<string>}} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label_key' => $this->labelKey,
            'minimum' => $this->minimum,
            'maximum' => $this->maximum,
            ...($this->assignmentPolicy instanceof SignatureParticipantAssignmentPolicy
                ? ['assignment_policy' => $this->assignmentPolicy->toArray()]
                : []),
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            labelKey: $data['label_key'],
            minimum: $data['minimum'],
            maximum: $data['maximum'],
            assignmentPolicy: ! array_key_exists('assignment_policy', $data)
                ? null
                : (is_array($data['assignment_policy'])
                    ? SignatureParticipantAssignmentPolicy::fromArray($data['assignment_policy'])
                    : throw new InvalidArgumentException('Serialized signature signatory role assignment policy must be an object.')),
        );
    }
}
