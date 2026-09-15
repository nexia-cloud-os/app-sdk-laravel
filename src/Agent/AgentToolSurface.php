<?php

declare(strict_types=1);

namespace Nexia\Agent;

/** Product surface a tool reads or operates. */
enum AgentToolSurface: string
{
    case None = 'none';
    case Screen = 'screen';
    case Canvas = 'canvas';
    case Data = 'data';
    case Setup = 'setup';
    case Process = 'process';
    case Dashboard = 'dashboard';
    case File = 'file';
    case Web = 'web';
}
