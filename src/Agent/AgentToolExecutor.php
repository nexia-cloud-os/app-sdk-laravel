<?php

declare(strict_types=1);

namespace Nexia\Agent;

/** Runtime that owns the executable handler for an Agent tool. */
enum AgentToolExecutor: string
{
    case Laravel = 'laravel';
    case Gateway = 'gateway';
}
