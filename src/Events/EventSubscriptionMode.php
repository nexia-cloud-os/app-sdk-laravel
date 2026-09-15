<?php

declare(strict_types=1);

namespace Nexia\Events;

/** Scope of public Event names accepted by an App listener registration. */
enum EventSubscriptionMode: string
{
    case Exact = 'exact';
    case AllPublicEvents = 'all_public_events';
}
