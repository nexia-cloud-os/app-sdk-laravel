<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureDocumentSourceKind;

interface SignatureDocumentPlanSource
{
    public function kind(): SignatureDocumentSourceKind;
}
