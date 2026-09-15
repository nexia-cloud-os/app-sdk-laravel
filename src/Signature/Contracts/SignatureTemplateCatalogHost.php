<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureTemplateCatalogQuery;
use Nexia\Signature\SignatureTemplateCatalogResult;

interface SignatureTemplateCatalogHost
{
    public function eligible(SignatureTemplateCatalogQuery $query): SignatureTemplateCatalogResult;
}
