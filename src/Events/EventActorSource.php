<?php

declare(strict_types=1);

namespace Nexia\Events;

/** Explicit authority source for a delegated consumer; the host restores and rechecks it. */
enum EventActorSource: string
{
    case Envelope = 'envelope';
    case SignatureRequester = 'signature_requester';
}
