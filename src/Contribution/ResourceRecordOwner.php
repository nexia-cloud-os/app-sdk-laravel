<?php

declare(strict_types=1);

namespace Nexia\Contribution;

enum ResourceRecordOwner: string
{
    case Tenant = 'tenant';
    case LegalEntity = 'legal_entity';
}
