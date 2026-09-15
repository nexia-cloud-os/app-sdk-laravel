<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum SignatureVariableType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Money = 'money';
}
