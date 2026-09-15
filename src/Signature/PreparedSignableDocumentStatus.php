<?php

declare(strict_types=1);

namespace Nexia\Signature;

enum PreparedSignableDocumentStatus: string
{
    case Preparing = 'preparing';
    case Materialized = 'materialized';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
