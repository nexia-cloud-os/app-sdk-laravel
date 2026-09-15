<?php

declare(strict_types=1);

namespace Nexia\Signature;

/** Formatter names implemented by the host for v1 document mappings. */
enum SignatureDocumentDataFormatter: string
{
    case Plain = 'plain';
    case DateIso = 'date_iso';
    case DateLocal = 'date_local';
    case DateTimeLocal = 'datetime_local';
    case BooleanYesNo = 'boolean_yes_no';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case MoneyWithCurrency = 'money_with_currency';
    case ResourceLabel = 'resource_label';
}
