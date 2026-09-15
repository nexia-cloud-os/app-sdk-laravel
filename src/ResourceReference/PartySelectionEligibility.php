<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

enum PartySelectionEligibility: string
{
    case None = 'none';
    case ActiveLegalEntityMember = 'active_legal_entity_member';
}
