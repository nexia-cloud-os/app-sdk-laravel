<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/**
 * App-authorized request input. The host reauthorizes its context and freezes
 * this transport snapshot; `toArray()` therefore must never be written to logs.
 */
final readonly class SignatureRequestSubmission
{
    /** @param non-empty-list<SignatureParticipantSnapshot> $participants */
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public SignableDocumentReference $expectedDocument,
        public array $participants,
        public SignatureConsentPolicyReference $consentPolicy,
        public SignatureExpiryPolicy $expiryPolicy,
        public string $idempotencyKey,
        public string $correlationId,
        public ?string $causationId = null,
    ) {
        foreach (['idempotencyKey' => $idempotencyKey, 'correlationId' => $correlationId] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature request submission {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($idempotencyKey) > 191) {
            throw new InvalidArgumentException('Signature request submission idempotencyKey is oversized.');
        }

        $uuidPattern = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match($uuidPattern, $correlationId) !== 1
            || ($causationId !== null && preg_match($uuidPattern, $causationId) !== 1)) {
            throw new InvalidArgumentException('Signature request correlation and causation identities must be UUIDs.');
        }

        if ($causationId !== null && ($causationId === '' || $causationId !== trim($causationId))) {
            throw new InvalidArgumentException('Signature request causationId must be null or normalized and non-blank.');
        }

        if (! array_is_list($participants) || $participants === []) {
            throw new InvalidArgumentException('Signature request participants must be a non-empty list.');
        }

        $identities = [];
        $sequences = [];
        foreach ($participants as $participant) {
            if (! $participant instanceof SignatureParticipantSnapshot) {
                throw new InvalidArgumentException('Signature request participants must use immutable SDK participant snapshots.');
            }

            $identity = $participant->roleKey."\0".$participant->sequence;
            if (isset($identities[$identity])) {
                throw new InvalidArgumentException('Signature request participants cannot repeat the same role and sequence.');
            }
            $identities[$identity] = true;

            if (isset($sequences[$participant->sequence])) {
                throw new InvalidArgumentException('Signature request participant sequence must identify exactly one participant.');
            }
            $sequences[$participant->sequence] = true;
        }
    }

    /**
     * @return array{
     *   legal_entity_public_id: string,
     *   actor_public_id: string,
     *   subject: array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string},
     *   expected_document: array{public_id: string, revision: int, checksum: string},
     *   participants: non-empty-list<array<string, mixed>>,
     *   consent_policy: array{policy_key: string, version: string},
     *   expiry_policy: array{expires_at: string, on_expiry: string},
     *   idempotency_key: string,
     *   correlation_id: string,
     *   causation_id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'legal_entity_public_id' => $this->legalEntity->publicId(),
            'actor_public_id' => $this->actor->publicId(),
            'subject' => $this->subject->toArray(),
            'expected_document' => $this->expectedDocument->toArray(),
            'participants' => array_map(
                static fn (SignatureParticipantSnapshot $participant): array => $participant->toArray(),
                $this->participants,
            ),
            'consent_policy' => $this->consentPolicy->toArray(),
            'expiry_policy' => $this->expiryPolicy->toArray(),
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
        ];
    }

    /**
     * Projection appropriate for logs, traces, and error context. It omits all
     * participant names and contact addresses.
     *
     * @return array<string, mixed>
     */
    public function toLogSafeArray(): array
    {
        return [
            'legal_entity_public_id' => $this->legalEntity->publicId(),
            'actor_public_id' => $this->actor->publicId(),
            'subject' => $this->subject->toArray(),
            'expected_document' => $this->expectedDocument->toArray(),
            'participants' => array_map(
                static fn (SignatureParticipantSnapshot $participant): array => $participant->toLogSafeArray(),
                $this->participants,
            ),
            'consent_policy' => $this->consentPolicy->toArray(),
            'expiry_policy' => $this->expiryPolicy->toArray(),
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
        ];
    }

    /**
     * Rehydrates transport data only after the enclosing runtime has restored
     * and checked the host context represented by its public identities.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, LegalEntity $legalEntity, Actor $actor): self
    {
        self::assertSerializedContext($data, $legalEntity, $actor);

        $subject = $data['subject'] ?? null;
        $expectedDocument = $data['expected_document'] ?? null;
        $participants = $data['participants'] ?? null;
        $consentPolicy = $data['consent_policy'] ?? null;
        $expiryPolicy = $data['expiry_policy'] ?? null;

        if (! is_array($subject)
            || ! is_array($expectedDocument)
            || ! is_array($participants)
            || ! array_is_list($participants)
            || ! is_array($consentPolicy)
            || ! is_array($expiryPolicy)) {
            throw new InvalidArgumentException('Signature request submission serialization is invalid.');
        }

        $snapshots = [];
        foreach ($participants as $participant) {
            if (! is_array($participant)) {
                throw new InvalidArgumentException('Signature request participant serialization is invalid.');
            }
            $snapshots[] = SignatureParticipantSnapshot::fromArray($participant);
        }

        return new self(
            legalEntity: $legalEntity,
            actor: $actor,
            subject: ResourceRef::fromArray($subject),
            expectedDocument: SignableDocumentReference::fromArray($expectedDocument),
            participants: $snapshots,
            consentPolicy: SignatureConsentPolicyReference::fromArray($consentPolicy),
            expiryPolicy: SignatureExpiryPolicy::fromArray($expiryPolicy),
            idempotencyKey: $data['idempotency_key'],
            correlationId: $data['correlation_id'],
            causationId: $data['causation_id'] ?? null,
        );
    }

    /** @param array<string, mixed> $data */
    private static function assertSerializedContext(array $data, LegalEntity $legalEntity, Actor $actor): void
    {
        if (($data['legal_entity_public_id'] ?? null) !== $legalEntity->publicId()
            || ($data['actor_public_id'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Signature request serialization does not match its restored host context.');
        }
    }
}
