<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Nexia\Laravel\Resources\ResourceWriteRoutes;

require dirname(__DIR__).'/vendor/autoload.php';

$model = 'Nexia\\Apps\\Example\\Notes\\Models\\Note';
$controller = 'Nexia\\Apps\\Example\\Notes\\Http\\Controllers\\NoteController';
$route = static fn (string $verb, string $uri, string $owner = ''): Route => new Route(
    $verb, $uri, ['uses' => ($owner ?: $controller).'@handle', 'controller' => ($owner ?: $controller).'@handle'],
);
$routes = [
    $route('GET', 'api/notes'),
    $route('POST', 'api/notes'),
    $route('GET', 'api/notes/{note}'),
    $route('PATCH', 'api/notes/{note}'),
    $route('DELETE', 'api/notes/{note}'),
    $route('POST', 'api/notes/{note}/submit'),
    $route('POST', 'web/notes'),
];
$resolve = static fn (array $routes): array => ResourceWriteRoutes::forModel(
    $model, ResourceWriteRoutes::byController($routes),
);
assert($resolve($routes) === [
    'create' => ['method' => 'POST', 'uri' => 'api/notes'],
    'update' => ['method' => 'PATCH', 'uri' => 'api/notes/{note}'],
    'delete' => ['method' => 'DELETE', 'uri' => 'api/notes/{note}'],
]);
assert($resolve([]) === []);
assert($resolve([$route('POST', 'api/notes')]) === []);
assert(! isset($resolve([...$routes, $route('POST', 'api/notes')])['create']));
assert($resolve([...$routes, $route('GET', 'api/admin/notes',
    'Nexia\\Apps\\Example\\Notes\\Http\\Controllers\\Admin\\NoteController')]) === []);
assert($resolve([
    $route('GET', 'api/notes', 'Other\\Http\\Controllers\\NoteController'),
    $route('POST', 'api/notes', 'Other\\Http\\Controllers\\NoteController'),
]) === []);
assert(ResourceWriteRoutes::isMemberRoute($routes[2]));
assert(! ResourceWriteRoutes::isMemberRoute($routes[5]));
assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController([$routes[2]]))
    === ['method' => 'GET', 'uri' => 'api/notes/{note}']);
assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController([$routes[0]])) === null);
assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController([
    $routes[2], $route('GET', 'api/archived-notes/{note}'),
])) === null);
assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController([
    $routes[2], $route('GET', 'api/admin/notes/{note}', 'Nexia\\Apps\\Example\\Notes\\Http\\Controllers\\Admin\\NoteController'),
])) === null);

// Standard Laravel actions disambiguate legacy scoped URLs, never route order.
$standard = static fn (string $verb, string $uri, string $method): Route => new Route(
    $verb, $uri, ['uses' => $controller.'@'.$method, 'controller' => $controller.'@'.$method],
);
$canonical = [
    $standard('GET', 'api/notes', 'index'),
    $standard('POST', 'api/notes', 'store'),
    $standard('GET', 'api/notes/{note}', 'show'),
    $standard('PUT', 'api/notes/{note}', 'update'),
    $standard('DELETE', 'api/notes/{note}', 'destroy'),
];
$legacy = [
    $standard('GET', 'api/legal-entities/{entity}/notes', 'legalEntityIndex'),
    $standard('POST', 'api/legal-entities/{entity}/notes', 'legalEntityStore'),
    $standard('GET', 'api/legal-entities/{entity}/notes/{note}', 'legalEntityShow'),
    $standard('PUT', 'api/legal-entities/{entity}/notes/{note}', 'legalEntityUpdate'),
    $standard('DELETE', 'api/legal-entities/{entity}/notes/{note}', 'legalEntityDestroy'),
];
foreach ([[...$canonical, ...$legacy], [...$legacy, ...$canonical]] as $combined) {
    assert($resolve($combined) === $resolve($canonical));
    assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController($combined))
        === ['method' => 'GET', 'uri' => 'api/notes/{note}']);
}
$ambiguous = [...$canonical,
    $standard('GET', 'api/archived-notes/{note}', 'show'),
    $standard('PUT', 'api/archived-notes/{note}', 'update'),
];
assert(! isset($resolve($ambiguous)['update']));
assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController($ambiguous)) === null);
// A read-only canonical endpoint must not acquire writes from a legacy URL.
assert($resolve([$canonical[0], $canonical[2], ...$legacy]) === []);
assert(ResourceWriteRoutes::detailForModel($model, ResourceWriteRoutes::byController($legacy))
    === ['method' => 'GET', 'uri' => 'api/legal-entities/{entity}/notes/{note}']);

echo "Resource write route discovery passed.\n";
