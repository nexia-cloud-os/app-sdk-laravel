<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Nexia\Http\Middleware;
use Nexia\Laravel\Http\Controllers\Controller;

require dirname(__DIR__).'/vendor/autoload.php';

$controller = new class extends Controller {};
$traits = class_uses(Controller::class);

if (! in_array(AuthorizesRequests::class, $traits, true)
    || ! method_exists($controller, 'authorize')
) {
    throw new RuntimeException('App controller authorization contract changed unexpectedly.');
}

if (Middleware::AUTHENTICATED_LOCALE !== 'nexia.locale') {
    throw new RuntimeException('Authenticated locale middleware alias changed unexpectedly.');
}

fwrite(STDOUT, "HTTP controller contract passed.\n");
