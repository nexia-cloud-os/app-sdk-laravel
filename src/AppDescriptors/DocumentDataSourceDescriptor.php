<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * App-owned document data shared by document consumers. The inherited value
 * contract deliberately retains the validation and wire compatibility of
 * existing Signature sources. Declare purposes explicitly when opting in.
 */
final readonly class DocumentDataSourceDescriptor extends SignatureDocumentDataSourceDescriptor
{
    // Metadata is paged separately. Per-resolution and snapshot bounds remain
    // unchanged, so adding fields never changes the identity of a source.
    protected const MAX_DESCRIPTOR_FIELDS = 1000;
    protected const MAX_DESCRIPTOR_VALUES = 1000;
    protected const MAX_SAMPLE_FIELDS = 1000;
    protected const MAX_DESCRIPTOR_BYTES = 524288;
}
