<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** One immutable template role/slot assignment projected without contact data. */
final readonly class SignatureTemplateParticipantAssignment
{
    public function __construct(
        public string $roleKey,
        public int $participantSlot,
        public SignatureParticipantAssignmentSource $source,
        public ?string $fixedPartyPublicId = null,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1
            || strlen($roleKey) > 191
            || $participantSlot < 1
            || $participantSlot > 65_535) {
            throw new InvalidArgumentException('Signature template participant assignment identity is invalid.');
        }
        if ($source === SignatureParticipantAssignmentSource::TemplateFixed) {
            if (! is_string($fixedPartyPublicId)
                || $fixedPartyPublicId !== strtolower($fixedPartyPublicId)
                || preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/D', $fixedPartyPublicId) !== 1) {
                throw new InvalidArgumentException('Template-fixed signature assignments require a canonical Party UUID.');
            }
        } elseif ($fixedPartyPublicId !== null) {
            throw new InvalidArgumentException('Only template-fixed signature assignments may carry a Party UUID.');
        }
    }

    /** @return array{role_key:string,participant_slot:int,assignment_source:string,fixed_party_public_id:string|null} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'participant_slot' => $this->participantSlot,
            'assignment_source' => $this->source->value,
            'fixed_party_public_id' => $this->fixedPartyPublicId,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $keys = ['role_key', 'participant_slot', 'assignment_source', 'fixed_party_public_id'];
        if (array_is_list($data)
            || array_keys($data) !== $keys
            || ! is_string($data['role_key'] ?? null)
            || ! is_int($data['participant_slot'] ?? null)
            || ! is_string($data['assignment_source'] ?? null)
            || ! array_key_exists('fixed_party_public_id', $data)
            || ($data['fixed_party_public_id'] !== null && ! is_string($data['fixed_party_public_id']))) {
            throw new InvalidArgumentException('Serialized signature template participant assignment shape is invalid.');
        }

        return new self(
            roleKey: $data['role_key'],
            participantSlot: $data['participant_slot'],
            source: SignatureParticipantAssignmentSource::from($data['assignment_source']),
            fixedPartyPublicId: is_string($data['fixed_party_public_id'])
                ? $data['fixed_party_public_id']
                : null,
        );
    }
}
