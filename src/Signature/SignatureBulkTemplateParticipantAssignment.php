<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Immutable assignment authority stored in one published template revision. */
final readonly class SignatureBulkTemplateParticipantAssignment
{
    public function __construct(
        public string $roleKey,
        public int $participantSlot,
        public SignatureBulkParticipantAssignmentSource $source,
        public ?string $fixedPartyPublicId = null,
    ) {
        self::assertSlot($roleKey, $participantSlot);

        $hasFixedParty = $fixedPartyPublicId !== null;
        if (($source === SignatureBulkParticipantAssignmentSource::TemplateFixed) !== $hasFixedParty) {
            throw new InvalidArgumentException('A signature bulk template assignment must carry a fixed Party exactly for template_fixed.');
        }
        if ($fixedPartyPublicId !== null) {
            self::assertPartyPublicId($fixedPartyPublicId);
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
        self::assertExactKeys($data, ['role_key', 'participant_slot', 'assignment_source', 'fixed_party_public_id']);

        return new self(
            roleKey: self::string($data, 'role_key'),
            participantSlot: self::integer($data, 'participant_slot'),
            source: SignatureBulkParticipantAssignmentSource::from(self::string($data, 'assignment_source')),
            fixedPartyPublicId: self::nullableString($data, 'fixed_party_public_id'),
        );
    }

    public function slotIdentity(): string
    {
        return $this->roleKey."\0".$this->participantSlot;
    }

    public static function assertSlot(string $roleKey, int $participantSlot): void
    {
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1
            || strlen($roleKey) > 100
            || $participantSlot < 1) {
            throw new InvalidArgumentException('Signature bulk participant role and slot are invalid.');
        }
    }

    public static function assertPartyPublicId(string $partyPublicId): void
    {
        if ($partyPublicId === '' || $partyPublicId !== trim($partyPublicId) || strlen($partyPublicId) > 191) {
            throw new InvalidArgumentException('Signature bulk Party public ID must be normalized, non-blank, and bounded.');
        }
    }

    /** @param array<string,mixed> $data @param list<string> $keys */
    public static function assertExactKeys(array $data, array $keys): void
    {
        if (array_is_list($data)
            || array_diff(array_keys($data), $keys) !== []
            || array_diff($keys, array_keys($data)) !== []) {
            throw new InvalidArgumentException('Serialized signature bulk contract shape is invalid.');
        }
    }

    /** @param array<string,mixed> $data */
    public static function string(array $data, string $key): string
    {
        return is_string($data[$key] ?? null)
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk [{$key}] must be a string.");
    }

    /** @param array<string,mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        return $data[$key] === null || is_string($data[$key])
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk [{$key}] must be null or a string.");
    }

    /** @param array<string,mixed> $data */
    public static function integer(array $data, string $key): int
    {
        return is_int($data[$key] ?? null)
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk [{$key}] must be an integer.");
    }
}
