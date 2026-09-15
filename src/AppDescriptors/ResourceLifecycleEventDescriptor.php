<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * Resource lifecycle event facet contributed through a resource descriptor.
 *
 * Lifecycle events are resource sub-facets rather than a separate top-level
 * catalog. Internal facets remain available to platform reactors and audit;
 * `publicForComposition` controls exposure to composition surfaces.
 */
final class ResourceLifecycleEventDescriptor
{
    /**
     * @param  array<string, mixed>  $payloadSchema
     * @param  list<CaseCorrelationSubjectDescriptor>  $caseCorrelationSubjects
     */
    public function __construct(
        public readonly string $key,
        public readonly string $labelKey,
        public readonly ?string $descriptionKey = null,
        public readonly array $payloadSchema = [],
        public readonly bool $publicForComposition = false,
        public readonly string $stability = 'stable',
        public readonly array $caseCorrelationSubjects = [],
        public readonly int $schemaVersion = 1,
    ) {
        if ($this->schemaVersion < 1) {
            throw DescriptorValidationException::lifecycleEvent(
                $this->key,
                'requires a positive schemaVersion.',
            );
        }

        if (! $this->publicForComposition) {
            PublicEventPayloadSchema::assertHandoffResultStates($this->key, $this->payloadSchema);

            return;
        }

        if (trim($this->key) === '') {
            throw DescriptorValidationException::lifecycleEvent(
                'unknown',
                'requires a non-empty key.',
            );
        }

        if (trim($this->labelKey) === '') {
            throw DescriptorValidationException::lifecycleEvent(
                $this->key,
                'requires a non-empty labelKey.',
            );
        }

        PublicEventPayloadSchema::assertValid($this->key, $this->payloadSchema);
    }
}
