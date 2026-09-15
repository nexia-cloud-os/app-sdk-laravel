<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Data handling class carried by App-contributed canonical variables. */
enum SignatureDataClassification: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';

    public function mayAppearInLogs(): bool
    {
        return $this === self::Public;
    }
}
