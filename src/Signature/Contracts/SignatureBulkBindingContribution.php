<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;

/**
 * Static bulk descriptors and their runtime providers from one owned contribution.
 *
 * The provider list may be empty while an App rolls a capability out or
 * removes it. Core joins exact descriptor/provider identities and therefore
 * treats every descriptor without a matching provider as unavailable.
 */
interface SignatureBulkBindingContribution extends AppDescriptorContribution
{
    /** @return list<SignatureBulkBindingProvider> */
    public function signatureBulkBindingProviders(): array;
}
