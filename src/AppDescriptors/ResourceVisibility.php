<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/** Coarse visibility granted by Core; an owning App may redact further but never widen it. */
enum ResourceVisibility: string
{
    case Owner = 'owner';
    case Active = 'active';
    case Past = 'past';
    case Stakeholder = 'stakeholder';
    case Auditor = 'auditor';
    case Search = 'search';
}
