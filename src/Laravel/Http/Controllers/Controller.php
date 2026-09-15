<?php

declare(strict_types=1);

namespace Nexia\Laravel\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller for App-owned HTTP endpoints hosted by Nexia Core.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
