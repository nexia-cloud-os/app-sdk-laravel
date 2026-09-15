<?php

declare(strict_types=1);

namespace Nexia\Events;

enum EventRecoveryMode: string
{
    case Replay = 'replay';
    case Reconcile = 'reconcile';
    case Ephemeral = 'ephemeral';
}
