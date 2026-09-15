<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use Nexia\ResourceReference\ResourceRef;

/**
 * Explicit, App-supplied protected data and provenance for one resource.
 * It intentionally has no model or serialization surface.
 */
final readonly class ResourceBackedSignatureDocumentData
{
    /**
     * @param  array<string, mixed>  $protectedValues
     * @param  array<string, mixed>  $displayValues
     */
    public function __construct(
        public ResourceRef $resourceRef,
        public ?string $sourceRevision,
        public ?string $contentHash,
        public ?DateTimeImmutable $effectiveAt,
        private array $protectedValues,
        private array $displayValues = [],
    ) {}

    /** @return array<string, mixed> */
    public function protectedValues(): array
    {
        return $this->protectedValues;
    }

    public function toResolvedItem(): SignatureDocumentDataResolvedItem
    {
        return new SignatureDocumentDataResolvedItem(
            resourceRef: $this->resourceRef,
            sourceRevision: $this->sourceRevision,
            contentHash: $this->contentHash,
            effectiveAt: $this->effectiveAt,
            values: $this->protectedValues,
            displayValues: $this->displayValues,
        );
    }
}
