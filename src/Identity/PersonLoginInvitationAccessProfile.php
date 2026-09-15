<?php

declare(strict_types=1);

namespace Nexia\Identity;

/** Core-resolved access intent attached to a person login invitation. */
enum PersonLoginInvitationAccessProfile: string
{
    /** Baseline Legal Entity member Self access at Legal Entity and Tenant scope. */
    case LegalEntityMemberSelfService = 'legal_entity_member_self_service';
}
