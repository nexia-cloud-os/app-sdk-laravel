<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\AppDescriptors\SignatureDocumentDataFieldDescriptor;
use Nexia\ResourceReference\ResourceRef;

/**
 * Protected typed output from an owner App. Values have no ordinary array or
 * JSON serialization surface; the host must intentionally retrieve them when
 * creating its encrypted preparation snapshot.
 */
final class SignatureDocumentDataResolvedItem
{
    /** @var array<string, mixed> */
    private array $values;

    /** @var array<string, mixed> */
    private array $displayValues;

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $displayValues
     */
    public function __construct(
        public readonly ResourceRef $resourceRef,
        public readonly ?string $sourceRevision,
        public readonly ?string $contentHash,
        public readonly ?DateTimeImmutable $effectiveAt,
        array $values,
        array $displayValues = [],
    ) {
        SignatureDocumentDataCandidate::assertProvenance($sourceRevision, $contentHash, 'resolved item');

        if (array_is_list($values) || $values === [] || count($values) > SignatureDocumentDataLimits::MAX_RESOLVED_ITEM_FIELDS) {
            throw new InvalidArgumentException('Signature document data resolved item values must be a bounded non-empty object.');
        }
        foreach ($values as $key => $value) {
            if (! is_string($key) || ! SignatureDocumentDataFieldDescriptor::isCanonicalKey($key)) {
                throw new InvalidArgumentException('Signature document data resolved item value keys must be canonical field keys.');
            }
            SignatureDocumentDataLimits::assertBoundedJsonValue($value, "Signature document data resolved item value [{$key}]");
        }
        if (($displayValues !== [] && array_is_list($displayValues)) || count($displayValues) > count($values)) {
            throw new InvalidArgumentException('Signature document data resolved item display values must be a bounded object.');
        }
        foreach ($displayValues as $key => $value) {
            if (! is_string($key) || ! array_key_exists($key, $values)) {
                throw new InvalidArgumentException('Signature document data resolved item display values must correspond to protected values.');
            }
            SignatureDocumentDataLimits::assertBoundedJsonValue($value, "Signature document data resolved item display value [{$key}]");
        }
        SignatureDocumentDataLimits::assertPayloadByteLength(
            [
                'values' => $values,
                'display_values' => $displayValues,
            ],
            SignatureDocumentDataLimits::MAX_SOURCE_RESULT_BYTES,
            'Signature document data resolved item payload',
        );

        $this->values = $values;
        $this->displayValues = $displayValues;
    }

    /** @return array<string, mixed> */
    public function protectedValues(): array
    {
        return $this->values;
    }

    /** @return array<string, mixed> */
    public function displayValues(): array
    {
        return $this->displayValues;
    }

    /** @return array{resource_ref: array<string, mixed>, source_revision: string|null, content_hash: string|null, effective_at: string|null} */
    public function provenance(): array
    {
        return [
            'resource_ref' => $this->resourceRef->toArray(),
            'source_revision' => $this->sourceRevision,
            'content_hash' => $this->contentHash,
            'effective_at' => $this->effectiveAt?->format(DATE_ATOM),
        ];
    }
}
