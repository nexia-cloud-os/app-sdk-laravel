<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureBulkBindingReauthorizationStatus: string
{
    case Authorized = 'authorized';
    case Denied = 'denied';
    case Unavailable = 'unavailable';
}
