<?php

declare(strict_types=1);

namespace Nexia\Contribution;

enum ResourceLegalEntityParticipation: string
{
    case None = 'none';
    case RecordOwner = 'record_owner';
}
