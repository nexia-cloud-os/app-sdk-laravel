<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureInvitationChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
}
