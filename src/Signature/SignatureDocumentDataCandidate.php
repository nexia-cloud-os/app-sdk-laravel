<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/** Safe selection candidate; this deliberately contains no protected values. */
final readonly class SignatureDocumentDataCandidate
{
    public function __construct(
        public ResourceRef $resourceRef,
        public ?string $sourceRevision,
        public ?string $contentHash,
        public ?DateTimeImmutable $effectiveAt = null,
    ) {
        self::assertProvenance($sourceRevision, $contentHash, 'candidate');
    }

    /** @return array{resource_ref: array<string, mixed>, source_revision: string|null, content_hash: string|null, effective_at: string|null} */
    public function toArray(): array
    {
        return [
            'resource_ref' => $this->resourceRef->toArray(),
            'source_revision' => $this->sourceRevision,
            'content_hash' => $this->contentHash,
            'effective_at' => $this->effectiveAt?->format(DATE_ATOM),
        ];
    }

    public static function assertProvenance(?string $sourceRevision, ?string $contentHash, string $context): void
    {
        if ($sourceRevision === null && $contentHash === null) {
            throw new InvalidArgumentException("Signature document data {$context} must declare a source revision or content hash.");
        }
        if ($sourceRevision !== null && ($sourceRevision === '' || $sourceRevision !== trim($sourceRevision) || mb_strlen($sourceRevision) > 191)) {
            throw new InvalidArgumentException("Signature document data {$context} source revision is invalid.");
        }
        if ($contentHash !== null && preg_match('/\A[0-9a-f]{64}\z/D', $contentHash) !== 1) {
            throw new InvalidArgumentException("Signature document data {$context} content hash must be a SHA-256 hex digest.");
        }
    }
}
