<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;

/**
 * One App contribution combines static source descriptors (through the normal
 * AppDescriptorSet) with runtime owner providers. Core joins both by the
 * provider's source key and version and fails closed on a mismatch.
 */
interface SignatureDocumentDataSourceContribution extends AppDescriptorContribution
{
    /** @return list<SignatureDocumentDataSourceProvider> */
    public function signatureDocumentDataSourceProviders(): array;
}
