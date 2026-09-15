<?php

declare(strict_types=1);

namespace Nexia\Agent;

/**
 * Stable task lanes used by the host Tool Router.
 *
 * A lane selects one coherent execution path before the model sees tools. It
 * is not a prompt hint and must not be inferred from a tool's namespace.
 */
enum AgentToolLane: string
{
    case Discovery = 'discovery';
    case Navigation = 'navigation';
    case CurrentScreen = 'current_screen';
    case CanvasDraft = 'canvas_draft';
    case TypedUiAction = 'typed_ui_action';
    case DirectData = 'direct_data';
    case Setup = 'setup';
    case Process = 'process';
    case Decision = 'decision';
    case Dashboard = 'dashboard';
    case File = 'file';
    case Research = 'research';
    case Session = 'session';
    case General = 'general';
}
