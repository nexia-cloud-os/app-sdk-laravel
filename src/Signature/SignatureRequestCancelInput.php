<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Idempotent request cancellation command; Core owns terminal-state eligibility. */
final readonly class SignatureRequestCancelInput
{
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $requestPublicId,
        public string $idempotencyKey,
        public string $correlationId,
        public ?string $causationId = null,
    ) {
        self::assertActionIdentity($requestPublicId, $idempotencyKey, $correlationId, $causationId);
    }

    /**
     * @return array{
     *   legal_entity_public_id: string,
     *   actor_public_id: string,
     *   subject: array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string},
     *   signature_request_public_id: string,
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
            'signature_request_public_id' => $this->requestPublicId,
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, LegalEntity $legalEntity, Actor $actor): self
    {
        self::assertSerializedContext($data, $legalEntity, $actor);

        $subject = $data['subject'] ?? null;
        if (! is_array($subject)) {
            throw new InvalidArgumentException('Signature request cancellation serialization is invalid.');
        }

        return new self(
            legalEntity: $legalEntity,
            actor: $actor,
            subject: ResourceRef::fromArray($subject),
            requestPublicId: $data['signature_request_public_id'],
            idempotencyKey: $data['idempotency_key'],
            correlationId: $data['correlation_id'],
            causationId: $data['causation_id'] ?? null,
        );
    }

    private static function assertActionIdentity(
        string $requestPublicId,
        string $idempotencyKey,
        string $correlationId,
        ?string $causationId,
    ): void {
        foreach (['requestPublicId' => $requestPublicId, 'idempotencyKey' => $idempotencyKey, 'correlationId' => $correlationId] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature request cancellation {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($idempotencyKey) > 191) {
            throw new InvalidArgumentException('Signature request cancellation idempotencyKey is oversized.');
        }

        $uuidPattern = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match($uuidPattern, $correlationId) !== 1
            || ($causationId !== null && preg_match($uuidPattern, $causationId) !== 1)) {
            throw new InvalidArgumentException('Signature request cancellation correlation and causation identities must be UUIDs.');
        }

        if ($causationId !== null && ($causationId === '' || $causationId !== trim($causationId))) {
            throw new InvalidArgumentException('Signature request cancellation causationId must be null or normalized and non-blank.');
        }
    }

    /** @param array<string, mixed> $data */
    private static function assertSerializedContext(array $data, LegalEntity $legalEntity, Actor $actor): void
    {
        if (($data['legal_entity_public_id'] ?? null) !== $legalEntity->publicId()
            || ($data['actor_public_id'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Signature request cancellation serialization does not match its restored host context.');
        }
    }
}
