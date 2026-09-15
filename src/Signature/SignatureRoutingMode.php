<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Immutable routing policy frozen from the published signature template. */
enum SignatureRoutingMode: string
{
    case Parallel = 'parallel';
    case Sequential = 'sequential';
}
