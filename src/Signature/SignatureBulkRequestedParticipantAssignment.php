<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Caller-controlled Party or one-time external recipient for one request_supplied slot. */
final readonly class SignatureBulkRequestedParticipantAssignment
{
    public function __construct(
        public string $roleKey,
        public int $participantSlot,
        public ?string $partyPublicId,
        public string $recipientType = 'party',
        public ?string $displayName = null,
        public ?string $email = null,
    ) {
        SignatureBulkTemplateParticipantAssignment::assertSlot($roleKey, $participantSlot);
        if ($recipientType === 'party') {
            SignatureBulkTemplateParticipantAssignment::assertPartyPublicId((string) $partyPublicId);
            if ($displayName !== null || $email !== null) {
                throw new \InvalidArgumentException('A Party bulk participant cannot carry external recipient facts.');
            }

            return;
        }
        $normalizedName = trim((string) $displayName);
        $normalizedEmail = strtolower(trim((string) $email));
        if ($recipientType !== 'external' || $partyPublicId !== null
            || $normalizedName === '' || strlen($normalizedName) > 255
            || $normalizedEmail === '' || strlen($normalizedEmail) > 320
            || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false
            || $normalizedName !== $displayName || $normalizedEmail !== $email) {
            throw new \InvalidArgumentException('An external bulk participant needs one normalized name and email address.');
        }
    }

    public static function external(string $roleKey, int $participantSlot, string $displayName, string $email): self
    {
        return new self($roleKey, $participantSlot, null, 'external', trim($displayName), strtolower(trim($email)));
    }

    public function source(): SignatureBulkParticipantAssignmentSource
    {
        return SignatureBulkParticipantAssignmentSource::RequestSupplied;
    }

    /** @return array{role_key:string,participant_slot:int,recipient_type:string,party_public_id:?string,display_name:?string,email:?string} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'participant_slot' => $this->participantSlot,
            'recipient_type' => $this->recipientType,
            'party_public_id' => $this->partyPublicId,
            'display_name' => $this->displayName,
            'email' => $this->email,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'role_key',
            'participant_slot',
            'recipient_type',
            'party_public_id',
            'display_name',
            'email',
        ]);

        return new self(
            roleKey: SignatureBulkTemplateParticipantAssignment::string($data, 'role_key'),
            participantSlot: SignatureBulkTemplateParticipantAssignment::integer($data, 'participant_slot'),
            partyPublicId: SignatureBulkTemplateParticipantAssignment::nullableString($data, 'party_public_id'),
            recipientType: SignatureBulkTemplateParticipantAssignment::string($data, 'recipient_type'),
            displayName: SignatureBulkTemplateParticipantAssignment::nullableString($data, 'display_name'),
            email: SignatureBulkTemplateParticipantAssignment::nullableString($data, 'email'),
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
            : 'external:'.hash('sha256', strtolower((string) $this->email));
    }
}
