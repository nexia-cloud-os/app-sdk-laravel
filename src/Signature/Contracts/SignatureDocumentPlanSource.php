<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

interface SignatureDocumentPlanSource
{
    public function kind(): SignatureDocumentSourceKind;
}
