<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** The request lifecycle outcome the host must apply when its deadline passes. */
enum SignatureExpiryAction: string
{
    case Expire = 'expire';
}
