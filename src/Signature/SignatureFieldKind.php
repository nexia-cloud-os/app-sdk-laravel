<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureFieldKind: string
{
    case Text = 'text';
    case Checkbox = 'checkbox';
    case Date = 'date';
    case Select = 'select';
    case SignerName = 'signer_name';
    case Signature = 'signature';
    case SignedAt = 'signed_at';
}
