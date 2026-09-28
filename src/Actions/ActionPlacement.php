<?php

declare(strict_types=1);

namespace Nexia\Actions;

enum ActionPlacement: string
{
    case Resource = 'resource';
    case Inspector = 'inspector';
    case Report = 'report';
    case Dashboard = 'dashboard';
    case Palette = 'palette';
    case Agent = 'agent';
}
