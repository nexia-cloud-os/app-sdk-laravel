<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureCapabilityStatus: string
{
    case Operational = 'operational';
    case Unavailable = 'unavailable';
}
