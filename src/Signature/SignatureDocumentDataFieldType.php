<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** The closed v1 value vocabulary for signature document data fields. */
enum SignatureDocumentDataFieldType: string
{
    case String = 'string';
    case Text = 'text';
    case Date = 'date';
    case DateTime = 'datetime';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Money = 'money';
    case ResourceRef = 'resource_ref';
    case Object = 'object';
    case List = 'list';
}
