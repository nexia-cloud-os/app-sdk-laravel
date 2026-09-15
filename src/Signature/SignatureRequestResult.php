<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/** Stable accepted-or-existing result for an idempotent request submission. */
final readonly class SignatureRequestResult
{
    public function __construct(
        public string $publicId,
        public SignatureRequestSubmissionStatus $submissionStatus,
        public SignatureRequestStatus $status,
        public ResourceRef $subject,
        public SignableDocumentReference $document,
        public ?string $predecessorPublicId = null,
        public SignatureRoutingMode $routingMode = SignatureRoutingMode::Parallel,
    ) {
        if ($publicId === '' || $publicId !== trim($publicId)) {
            throw new InvalidArgumentException('Signature request result publicId must be normalized and non-blank.');
        }

        if ($predecessorPublicId !== null
            && ($predecessorPublicId === ''
                || $predecessorPublicId !== trim($predecessorPublicId)
                || $predecessorPublicId === $publicId)) {
            throw new InvalidArgumentException('Signature request result predecessorPublicId is invalid.');
        }
    }

    /**
     * @return array{
     *   signature_request_public_id: string,
     *   submission_status: string,
     *   status: string,
     *   subject: array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string},
     *   document: array{public_id: string, revision: int, checksum: string},
     *   predecessor_signature_request_public_id: string|null,
     *   routing_mode: string
     * }
     */
    public function toArray(): array
    {
        return [
            'signature_request_public_id' => $this->publicId,
            'submission_status' => $this->submissionStatus->value,
            'status' => $this->status->value,
            'subject' => $this->subject->toArray(),
            'document' => $this->document->toArray(),
            'predecessor_signature_request_public_id' => $this->predecessorPublicId,
            'routing_mode' => $this->routingMode->value,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $subject = $data['subject'] ?? null;
        $document = $data['document'] ?? null;

        if (! is_array($subject) || ! is_array($document)) {
            throw new InvalidArgumentException('Signature request result serialization is invalid.');
        }

        return new self(
            publicId: $data['signature_request_public_id'],
            submissionStatus: SignatureRequestSubmissionStatus::from($data['submission_status']),
            status: SignatureRequestStatus::from($data['status']),
            subject: ResourceRef::fromArray($subject),
            document: SignableDocumentReference::fromArray($document),
            predecessorPublicId: $data['predecessor_signature_request_public_id'] ?? null,
            routingMode: isset($data['routing_mode'])
                ? SignatureRoutingMode::from($data['routing_mode'])
                : SignatureRoutingMode::Parallel,
        );
    }
}
