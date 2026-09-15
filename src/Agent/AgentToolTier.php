<?php

declare(strict_types=1);

namespace Nexia\Agent;

/** Default confirmation policy shipped with an App-owned Agent tool. */
enum AgentToolTier: string
{
    case Auto = 'auto';
    case Confirm = 'confirm';
}
