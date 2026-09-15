<?php

declare(strict_types=1);

namespace Nexia\Dashboard;

enum RendererCapability
{
    case HumanOnly;
    case HumanAndAgent;
    case AgentOnly;

    public function allowsAgent(): bool
    {
        return $this === self::HumanAndAgent || $this === self::AgentOnly;
    }

    public function allowsHumanDashboard(): bool
    {
        return $this === self::HumanOnly || $this === self::HumanAndAgent;
    }
}
