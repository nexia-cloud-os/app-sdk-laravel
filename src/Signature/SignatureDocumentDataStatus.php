<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Non-probing public resolution statuses for signature document data. */
enum SignatureDocumentDataStatus: string
{
    case Resolved = 'resolved';
    case SelectionRequired = 'selection_required';
    case Missing = 'missing';
    case Stale = 'stale';
    case Unavailable = 'unavailable';
}
