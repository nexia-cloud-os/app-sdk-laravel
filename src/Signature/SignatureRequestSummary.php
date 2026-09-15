<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/**
 * Read-only request metadata safe for an authorized adopting App and for logs.
 * Participant names, invitation addresses, challenges, and PDF content are
 * intentionally absent.
 */
final readonly class SignatureRequestSummary
{
    /** @param list<SignatureParticipantProgress> $participants */
    public function __construct(
        public string $publicId,
        public SignatureRequestStatus $status,
        public ResourceRef $subject,
        public SignableDocumentReference $document,
        public string $expiresAt,
        public array $participants,
        public ?string $predecessorPublicId = null,
        public ?SignatureErrorCode $failureCode = null,
        public ?SignatureRequestArtifactReference $completedArtifact = null,
        public SignatureRoutingMode $routingMode = SignatureRoutingMode::Parallel,
    ) {
        if ($publicId === '' || $publicId !== trim($publicId)) {
            throw new InvalidArgumentException('Signature request summary publicId must be normalized and non-blank.');
        }

        if (! self::isIsoTimestamp($expiresAt)) {
            throw new InvalidArgumentException('Signature request summary expiry must be an ISO-8601 timestamp with an explicit timezone.');
        }

        if ($predecessorPublicId !== null
            && ($predecessorPublicId === ''
                || $predecessorPublicId !== trim($predecessorPublicId)
                || $predecessorPublicId === $publicId)) {
            throw new InvalidArgumentException('Signature request summary predecessorPublicId is invalid.');
        }

        if (! array_is_list($participants)) {
            throw new InvalidArgumentException('Signature request summary participant progress must be a list.');
        }

        $identities = [];
        $sequences = [];
        foreach ($participants as $participant) {
            if (! $participant instanceof SignatureParticipantProgress) {
                throw new InvalidArgumentException('Signature request summary participants must use log-safe progress entries.');
            }

            $identity = $participant->roleKey."\0".$participant->roleSlot;
            if (isset($identities[$identity])) {
                throw new InvalidArgumentException('Signature request summary participant progress cannot repeat the same role and slot.');
            }
            $identities[$identity] = true;

            if (isset($sequences[$participant->sequence])) {
                throw new InvalidArgumentException('Signature request summary participant sequence must identify exactly one participant.');
            }
            $sequences[$participant->sequence] = true;
        }
    }

    /**
     * @return array{
     *   signature_request_public_id: string,
     *   status: string,
     *   subject: array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string},
     *   document: array{public_id: string, revision: int, checksum: string},
     *   routing_mode: string,
     *   expires_at: string,
     *   participants: list<array{role_key: string, role_slot: int, sequence: int, status: string, routing_state: string, presented_revision: int, completed_at: string|null}>,
     *   predecessor_signature_request_public_id: string|null,
     *   failure_code: string|null,
     *   completed_artifact: array{public_id: string, completed_checksum: string, manifest_checksum: string, finalized_at: string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'signature_request_public_id' => $this->publicId,
            'status' => $this->status->value,
            'subject' => $this->subject->toArray(),
            'document' => $this->document->toArray(),
            'routing_mode' => $this->routingMode->value,
            'expires_at' => $this->expiresAt,
            'participants' => array_map(
                static fn (SignatureParticipantProgress $participant): array => $participant->toArray(),
                $this->participants,
            ),
            'predecessor_signature_request_public_id' => $this->predecessorPublicId,
            'failure_code' => $this->failureCode?->value,
            'completed_artifact' => $this->completedArtifact?->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    public function toLogSafeArray(): array
    {
        return $this->toArray();
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $subject = $data['subject'] ?? null;
        $document = $data['document'] ?? null;
        $participants = $data['participants'] ?? null;

        if (! is_array($subject)
            || ! is_array($document)
            || ! is_array($participants)
            || ! array_is_list($participants)) {
            throw new InvalidArgumentException('Signature request summary serialization is invalid.');
        }

        $progress = [];
        $legacyRoleSlots = [];
        foreach ($participants as $participant) {
            if (! is_array($participant)) {
                throw new InvalidArgumentException('Signature request participant progress serialization is invalid.');
            }

            $roleKey = $participant['role_key'] ?? null;
            if (! array_key_exists('role_slot', $participant) && is_string($roleKey)) {
                $legacyRoleSlots[$roleKey] = ($legacyRoleSlots[$roleKey] ?? 0) + 1;
                $participant['role_slot'] = $legacyRoleSlots[$roleKey];
            }

            $progress[] = SignatureParticipantProgress::fromArray($participant);
        }

        $failureCode = $data['failure_code'] ?? null;
        $artifact = $data['completed_artifact'] ?? null;
        if ($failureCode !== null && ! is_string($failureCode)) {
            throw new InvalidArgumentException('Signature request summary failure code must be null or a string.');
        }
        if ($artifact !== null && ! is_array($artifact)) {
            throw new InvalidArgumentException('Signature request summary completed artifact must be null or an object.');
        }

        return new self(
            publicId: $data['signature_request_public_id'],
            status: SignatureRequestStatus::from($data['status']),
            subject: ResourceRef::fromArray($subject),
            document: SignableDocumentReference::fromArray($document),
            routingMode: isset($data['routing_mode'])
                ? SignatureRoutingMode::from($data['routing_mode'])
                : SignatureRoutingMode::Parallel,
            expiresAt: $data['expires_at'],
            participants: $progress,
            predecessorPublicId: $data['predecessor_signature_request_public_id'] ?? null,
            failureCode: $failureCode === null ? null : SignatureErrorCode::from($failureCode),
            completedArtifact: $artifact === null ? null : SignatureRequestArtifactReference::fromArray($artifact),
        );
    }

    private static function isIsoTimestamp(string $value): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $value) !== 1) {
            return false;
        }

        try {
            new DateTimeImmutable($value);
        } catch (\Exception) {
            return false;
        }

        return true;
    }
}
