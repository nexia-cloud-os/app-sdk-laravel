<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/**
 * Idempotent invitation resend command. It identifies an existing immutable
 * request and never carries new document, participant, or policy values.
 */
final readonly class SignatureRequestResendInput
{
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $requestPublicId,
        public string $idempotencyKey,
        public string $correlationId,
        public ?int $participantSequence = null,
        public ?string $causationId = null,
    ) {
        foreach (['requestPublicId' => $requestPublicId, 'idempotencyKey' => $idempotencyKey, 'correlationId' => $correlationId] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature request resend {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($idempotencyKey) > 191 || ($participantSequence !== null && $participantSequence < 1)) {
            throw new InvalidArgumentException('Signature request resend contains an oversized idempotency key or invalid participant sequence.');
        }

        $uuidPattern = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match($uuidPattern, $correlationId) !== 1
            || ($causationId !== null && preg_match($uuidPattern, $causationId) !== 1)) {
            throw new InvalidArgumentException('Signature request resend correlation and causation identities must be UUIDs.');
        }

        if ($causationId !== null && ($causationId === '' || $causationId !== trim($causationId))) {
            throw new InvalidArgumentException('Signature request resend causationId must be null or normalized and non-blank.');
        }
    }

    /**
     * @return array{
     *   legal_entity_public_id: string,
     *   actor_public_id: string,
     *   subject: array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string},
     *   signature_request_public_id: string,
     *   idempotency_key: string,
     *   correlation_id: string,
     *   participant_sequence: int|null,
     *   causation_id: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'legal_entity_public_id' => $this->legalEntity->publicId(),
            'actor_public_id' => $this->actor->publicId(),
            'subject' => $this->subject->toArray(),
            'signature_request_public_id' => $this->requestPublicId,
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'participant_sequence' => $this->participantSequence,
            'causation_id' => $this->causationId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, LegalEntity $legalEntity, Actor $actor): self
    {
        self::assertSerializedContext($data, $legalEntity, $actor);

        $subject = $data['subject'] ?? null;
        if (! is_array($subject)) {
            throw new InvalidArgumentException('Signature request resend serialization is invalid.');
        }

        return new self(
            legalEntity: $legalEntity,
            actor: $actor,
            subject: ResourceRef::fromArray($subject),
            requestPublicId: $data['signature_request_public_id'],
            idempotencyKey: $data['idempotency_key'],
            correlationId: $data['correlation_id'],
            participantSequence: $data['participant_sequence'] ?? null,
            causationId: $data['causation_id'] ?? null,
        );
    }

    /** @param array<string, mixed> $data */
    private static function assertSerializedContext(array $data, LegalEntity $legalEntity, Actor $actor): void
    {
        if (($data['legal_entity_public_id'] ?? null) !== $legalEntity->publicId()
            || ($data['actor_public_id'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Signature request resend serialization does not match its restored host context.');
        }
    }
}
