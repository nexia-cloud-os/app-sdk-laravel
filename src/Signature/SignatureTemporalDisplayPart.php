<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Optional presentation-only extraction applied without weakening the stored value. */
enum SignatureTemporalDisplayPart: string
{
    case Date = 'date';
    case Time = 'time';
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';
}
