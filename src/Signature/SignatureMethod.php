<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureMethod: string
{
    case Electronic = 'electronic';
    case WetInk = 'wet_ink';
}
