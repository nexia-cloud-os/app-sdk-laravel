<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Final redacted recipient assignment returned after every authority is resolved. */
final readonly class SignatureBulkResolvedParticipantAssignment
{
    public function __construct(
        public string $roleKey,
        public int $participantSlot,
        public SignatureBulkParticipantAssignmentSource $source,
        public ?string $partyPublicId,
        public string $recipientType = 'party',
        public ?string $externalRecipientFingerprint = null,
    ) {
        SignatureBulkTemplateParticipantAssignment::assertSlot($roleKey, $participantSlot);
        if ($recipientType === 'party') {
            SignatureBulkTemplateParticipantAssignment::assertPartyPublicId((string) $partyPublicId);
            if ($externalRecipientFingerprint !== null) {
                throw new \InvalidArgumentException('A Party bulk assignment cannot carry an external recipient fingerprint.');
            }

            return;
        }
        if ($recipientType !== 'external' || $partyPublicId !== null
            || preg_match('/\A[0-9a-f]{64}\z/D', (string) $externalRecipientFingerprint) !== 1) {
            throw new \InvalidArgumentException('An external bulk assignment needs one redacted recipient fingerprint.');
        }
    }

    public static function external(
        string $roleKey,
        int $participantSlot,
        SignatureBulkParticipantAssignmentSource $source,
        string $email,
    ): self {
        return new self($roleKey, $participantSlot, $source, null, 'external', hash('sha256', strtolower(trim($email))));
    }

    /** @return array{role_key:string,participant_slot:int,assignment_source:string,recipient_type:string,party_public_id:?string,external_recipient_fingerprint:?string} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'participant_slot' => $this->participantSlot,
            'assignment_source' => $this->source->value,
            'recipient_type' => $this->recipientType,
            'party_public_id' => $this->partyPublicId,
            'external_recipient_fingerprint' => $this->externalRecipientFingerprint,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'role_key',
            'participant_slot',
            'assignment_source',
            'recipient_type',
            'party_public_id',
            'external_recipient_fingerprint',
        ]);

        return new self(
            roleKey: SignatureBulkTemplateParticipantAssignment::string($data, 'role_key'),
            participantSlot: SignatureBulkTemplateParticipantAssignment::integer($data, 'participant_slot'),
            source: SignatureBulkParticipantAssignmentSource::from(
                SignatureBulkTemplateParticipantAssignment::string($data, 'assignment_source'),
            ),
            partyPublicId: SignatureBulkTemplateParticipantAssignment::nullableString($data, 'party_public_id'),
            recipientType: SignatureBulkTemplateParticipantAssignment::string($data, 'recipient_type'),
            externalRecipientFingerprint: SignatureBulkTemplateParticipantAssignment::nullableString($data, 'external_recipient_fingerprint'),
        );
    }

    public function slotIdentity(): string
    {
        return $this->roleKey."\0".$this->participantSlot;
    }

    public function recipientIdentity(): string
    {
        return $this->recipientType === 'party'
            ? 'party:'.strtolower((string) $this->partyPublicId)
            : 'external:'.$this->externalRecipientFingerprint;
    }
}
