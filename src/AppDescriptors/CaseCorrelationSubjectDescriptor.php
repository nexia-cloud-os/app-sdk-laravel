<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * Immutable descriptor for an operational subject correlated from an event payload.
 *
 * The authored public input is the subject key. The runtime resolves that key
 * through this descriptor into a stable resource binding, so callers never
 * submit arbitrary payload paths as authority. `sourcePath` is the
 * descriptor-owned path whose value contributes to the operational case key.
 */
final class CaseCorrelationSubjectDescriptor
{
    public function __construct(
        public readonly string $key,
        public readonly string $labelKey,
        public readonly string $subjectResourceKey,
        public readonly string $sourceKind = 'payload',
        public readonly string $sourcePath = '',
        public readonly ?string $resourceIdPath = null,
        public readonly ?string $displayPath = null,
    ) {}
}
