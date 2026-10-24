<?php

declare(strict_types=1);

namespace Nexia\DocumentData\Contracts;

use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;
use Nexia\Signature\Contracts\SignatureDocumentDataSourceProvider;

interface DocumentDataSourceContribution extends AppDescriptorContribution
{
    /** @return list<SignatureDocumentDataSourceProvider> */
    public function documentDataSourceProviders(): array;
}
