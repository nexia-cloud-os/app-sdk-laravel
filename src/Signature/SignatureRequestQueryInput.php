<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Authorized metadata query scoped to one App-owned subject. */
final readonly class SignatureRequestQueryInput
{
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $requestPublicId,
    ) {
        if ($requestPublicId === '' || $requestPublicId !== trim($requestPublicId)) {
            throw new InvalidArgumentException('Signature request query public id must be normalized and non-blank.');
        }
    }

    /**
     * @return array{
     *   legal_entity_public_id: string,
     *   actor_public_id: string,
     *   subject: array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string},
     *   signature_request_public_id: string
     * }
     */
    public function toArray(): array
    {
        return [
            'legal_entity_public_id' => $this->legalEntity->publicId(),
            'actor_public_id' => $this->actor->publicId(),
            'subject' => $this->subject->toArray(),
            'signature_request_public_id' => $this->requestPublicId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, LegalEntity $legalEntity, Actor $actor): self
    {
        self::assertSerializedContext($data, $legalEntity, $actor);

        $subject = $data['subject'] ?? null;
        if (! is_array($subject)) {
            throw new InvalidArgumentException('Signature request query serialization is invalid.');
        }

        return new self(
            legalEntity: $legalEntity,
            actor: $actor,
            subject: ResourceRef::fromArray($subject),
            requestPublicId: $data['signature_request_public_id'],
        );
    }

    /** @param array<string, mixed> $data */
    private static function assertSerializedContext(array $data, LegalEntity $legalEntity, Actor $actor): void
    {
        if (($data['legal_entity_public_id'] ?? null) !== $legalEntity->publicId()
            || ($data['actor_public_id'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Signature request query serialization does not match its restored host context.');
        }
    }
}
