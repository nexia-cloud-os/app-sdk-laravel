<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/**
 * Creates a successor request while preserving its predecessor provenance.
 * The replacement may select a new ready document, participant, or policy
 * snapshot; Core verifies that it remains in the same authorized subject scope.
 */
final readonly class SignatureRequestReissueInput
{
    public function __construct(
        public string $predecessorRequestPublicId,
        public SignatureRequestSubmission $replacement,
    ) {
        if ($predecessorRequestPublicId === '' || $predecessorRequestPublicId !== trim($predecessorRequestPublicId)) {
            throw new InvalidArgumentException('Signature request reissue predecessor public id must be normalized and non-blank.');
        }
    }

    /** @return array{predecessor_signature_request_public_id: string, replacement: array<string, mixed>} */
    public function toArray(): array
    {
        return [
            'predecessor_signature_request_public_id' => $this->predecessorRequestPublicId,
            'replacement' => $this->replacement->toArray(),
        ];
    }

    /** @return array{predecessor_signature_request_public_id: string, replacement: array<string, mixed>} */
    public function toLogSafeArray(): array
    {
        return [
            'predecessor_signature_request_public_id' => $this->predecessorRequestPublicId,
            'replacement' => $this->replacement->toLogSafeArray(),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, LegalEntity $legalEntity, Actor $actor): self
    {
        $replacement = $data['replacement'] ?? null;
        if (! is_array($replacement)) {
            throw new InvalidArgumentException('Signature request reissue serialization is invalid.');
        }

        return new self(
            predecessorRequestPublicId: $data['predecessor_signature_request_public_id'],
            replacement: SignatureRequestSubmission::fromArray($replacement, $legalEntity, $actor),
        );
    }
}
