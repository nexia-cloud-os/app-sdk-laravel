<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureBulkBindingPreviewStatus: string
{
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Unavailable = 'unavailable';
}
