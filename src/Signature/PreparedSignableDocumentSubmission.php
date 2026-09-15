<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Complete App-authorized snapshot for a contract-specific uploaded PDF. */
final readonly class PreparedSignableDocumentSubmission
{
    /**
     * @param non-empty-list<SignatureParticipantSnapshot> $participants
     * @param non-empty-list<SignatureFieldDefinition> $fields
     */
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public string $uploadIntentId,
        public string $locale,
        public string $effectiveOn,
        public array $participants,
        public array $fields,
        public string $idempotencyKey,
        public string $correlationId,
        public ?string $causationId = null,
    ) {
        foreach (['bindingKey' => $bindingKey, 'bindingVersion' => $bindingVersion, 'uploadIntentId' => $uploadIntentId, 'locale' => $locale, 'idempotencyKey' => $idempotencyKey, 'correlationId' => $correlationId] as $name => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Prepared signable document {$name} must be normalized and non-blank.");
            }
        }
        if (strlen($bindingKey) > 191 || strlen($bindingVersion) > 32 || strlen($idempotencyKey) > 191
            || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,2}\z/D', $locale) !== 1) {
            throw new InvalidArgumentException('Prepared signable document identity is invalid.');
        }
        $uuid = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match($uuid, $uploadIntentId) !== 1 || preg_match($uuid, $correlationId) !== 1
            || ($causationId !== null && preg_match($uuid, $causationId) !== 1)) {
            throw new InvalidArgumentException('Prepared signable document UUID identity is invalid.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveOn);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $effectiveOn) {
            throw new InvalidArgumentException('Prepared signable document effectiveOn must be an ISO date.');
        }
        if (! array_is_list($participants) || $participants === [] || ! array_is_list($fields) || $fields === []) {
            throw new InvalidArgumentException('Prepared signable document participants and fields must be non-empty lists.');
        }
        foreach ($participants as $participant) {
            if (! $participant instanceof SignatureParticipantSnapshot) {
                throw new InvalidArgumentException('Prepared signable document participants are invalid.');
            }
        }
        foreach ($fields as $field) {
            if (! $field instanceof SignatureFieldDefinition) {
                throw new InvalidArgumentException('Prepared signable document fields are invalid.');
            }
        }
    }
}
