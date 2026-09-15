<?php

declare(strict_types=1);

namespace Nexia\Identity;

enum PersonLoginInvitationStatus: string
{
    case Available = 'available';
    case NotAuthorized = 'not_authorized';
    case ExistingAccess = 'existing_access';
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
