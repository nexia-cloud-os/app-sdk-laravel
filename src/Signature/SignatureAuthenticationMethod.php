<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureAuthenticationMethod: string
{
    case RequestPassword = 'request_password';
    case EmailOtp = 'email_otp';
    case SmsOtp = 'sms_otp';
}
