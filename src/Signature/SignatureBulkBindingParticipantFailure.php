<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Identifies the participant slot that caused a redacted preview rejection. */
final readonly class SignatureBulkBindingParticipantFailure
{
    public function __construct(
        public string $roleKey,
        public int $participantSlot,
        public SignatureBulkParticipantAssignmentSource $source,
    ) {
        if (trim($roleKey) === '' || $participantSlot < 1) {
            throw new InvalidArgumentException('Signature bulk participant failure identity is invalid.');
        }
    }

    /** @return array{role_key:string,participant_slot:int,assignment_source:string} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'participant_slot' => $this->participantSlot,
            'assignment_source' => $this->source->value,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'role_key',
            'participant_slot',
            'assignment_source',
        ]);

        return new self(
            SignatureBulkTemplateParticipantAssignment::string($data, 'role_key'),
            SignatureBulkTemplateParticipantAssignment::integer($data, 'participant_slot'),
            SignatureBulkParticipantAssignmentSource::from(
                SignatureBulkTemplateParticipantAssignment::string($data, 'assignment_source'),
            ),
        );
    }
}
