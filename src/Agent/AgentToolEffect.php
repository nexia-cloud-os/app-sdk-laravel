<?php

declare(strict_types=1);

namespace Nexia\Agent;

/** User-visible state effect of an Agent tool call. */
enum AgentToolEffect: string
{
    case Read = 'read';
    case Draft = 'draft';
    case Mutate = 'mutate';
    case External = 'external';
}
