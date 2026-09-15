<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Request actions require an already-ready document and explicit user intent. */
enum SignatureRequestAction: string
{
    case Submit = 'submit';
    case Cancel = 'cancel';
    case Resend = 'resend';
    case Reissue = 'reissue';
}
