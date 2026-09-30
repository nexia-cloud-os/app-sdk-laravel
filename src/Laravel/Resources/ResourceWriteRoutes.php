<?php

declare(strict_types=1);

namespace Nexia\Laravel\Resources;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/** Inspect registered owning-controller CRUD routes; this never grants execution authority. */
final class ResourceWriteRoutes
{
    private const ACTION_VERBS = ['create' => ['POST'], 'update' => ['PUT', 'PATCH'], 'delete' => ['DELETE']];

    /** @param array<string, list<Route>> $byController
     * @return array<string, array{method: string, uri: string}>
     */
    public static function forModel(string $modelClass, array $byController): array
    {
        $controller = self::controllerFor($modelClass, $byController);

        return $controller === null ? [] : self::actionsFor($byController[$controller]);
    }

    /** One unambiguous owning-controller GET member route; never a guessed URI.
     * @param array<string, list<Route>> $byController
     * @return array{method: string, uri: string}|null
     */
    public static function detailForModel(string $modelClass, array $byController): ?array
    {
        $controller = self::controllerFor($modelClass, $byController);
        $uris = $controller === null ? [] : self::readUrisFor($byController[$controller], member: true);

        return count($uris) === 1 ? ['method' => 'GET', 'uri' => array_key_first($uris)] : null;
    }

    /** The registered canonical collection endpoint, with no guessed path. */
    public static function listForModel(string $modelClass, array $byController): ?array
    {
        $controller = self::controllerFor($modelClass, $byController);
        $uris = $controller === null ? [] : self::readUrisFor($byController[$controller], member: false);

        return count($uris) === 1 ? ['method' => 'GET', 'uri' => array_key_first($uris)] : null;
    }

    /**
     * The one controller that owns this Resource, or null when zero or
     * several do.
     *
     * @param  array<string, list<Route>>  $byController
     */
    private static function controllerFor(string $modelClass, array $byController): ?string
    {
        $expected = class_basename($modelClass);
        $root = self::rootNamespaceOf($modelClass);

        $matches = [];
        foreach (array_keys($byController) as $class) {
            if (Str::beforeLast(class_basename($class), 'Controller') !== $expected) {
                continue;
            }
            if (! str_starts_with($class, $root.'\\')) {
                continue;
            }

            $matches[] = $class;
        }

        // Zero is "this Resource has no API of its own". More than one is
        // "somebody has to say which", and answering it by picking the
        // first would be the guess this class exists to avoid.
        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * Everything before `\Models\`, which is the package or app root.
     *
     * Falls back to the first segment for a model that does not live under
     * a `Models` namespace, which keeps host models (`App\...`) matched
     * against host controllers.
     */
    private static function rootNamespaceOf(string $modelClass): string
    {
        $position = strpos($modelClass, '\\Models\\');

        return $position === false
            ? explode('\\', $modelClass)[0]
            : substr($modelClass, 0, $position);
    }

    /**
     * Map each write action to the route that expresses it.
     *
     * @param  list<Route>  $routes
     * @return array<string, array{method: string, uri: string}>
     */
    private static function actionsFor(array $routes): array
    {
        $collectionUris = self::readUrisFor($routes, member: false);
        $memberUris = self::readUrisFor($routes, member: true);
        $actions = [];

        foreach (self::ACTION_VERBS as $action => $verbs) {
            $wantsMember = $action !== 'create';
            $readUris = $wantsMember ? $memberUris : $collectionUris;
            $candidates = [];

            foreach ($routes as $route) {
                $verb = self::writeVerbOf($route);
                if ($verb === null || ! in_array($verb, $verbs, true)) {
                    continue;
                }
                if (! isset($readUris[$route->uri()])) {
                    continue;
                }

                $candidates[] = ['method' => $verb, 'uri' => $route->uri()];
            }

            if (count($candidates) === 1) {
                $actions[$action] = $candidates[0];
            }
        }

        return $actions;
    }

    /**
     * The controller's canonical collection or member URIs, read from its
     * GET routes. Laravel index/show actions take precedence over compatibility
     * endpoints; multiple standard URIs still remain ambiguous. A write must
     * target one of these exact URIs; a trailing
     * lifecycle verb or another sub-resource is not a CRUD route.
     *
     * @param  list<Route>  $routes
     * @return array<string, true>
     */
    private static function readUrisFor(array $routes, bool $member): array
    {
        $uris = [];
        $standardUris = [];

        foreach ($routes as $route) {
            if (! in_array('GET', $route->methods(), true) || self::isMemberRoute($route) !== $member) {
                continue;
            }

            $uris[$route->uri()] = true;
            if ($route->getActionMethod() === ($member ? 'show' : 'index')) {
                $standardUris[$route->uri()] = true;
            }
        }

        return $standardUris !== [] ? $standardUris : $uris;
    }

    private static function writeVerbOf(Route $route): ?string
    {
        foreach ($route->methods() as $method) {
            if ($method === 'POST') {
                return 'POST';
            }
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $method;
            }
        }

        return null;
    }

    /**
     * True when the route addresses one record.
     *
     * Decided by the URI shape rather than by the controller method name:
     * a member route ends in a parameter. A sub-resource route such as
     * `.../records/{record}/audit` ends in a literal segment and is
     * therefore not the member route for a write — which is the point,
     * because sending an update there would call a different operation.
     */
    public static function isMemberRoute(Route $route): bool
    {
        $segments = explode('/', trim($route->uri(), '/'));
        $last = end($segments);

        return is_string($last) && str_starts_with($last, '{');
    }

    /** @return array<string, list<Route>> */
    public static function byController(iterable $routes): array
    {
        $byController = [];

        foreach ($routes as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $action = $route->getActionName();
            if (! str_contains($action, '@')) {
                continue;
            }

            $byController[explode('@', $action, 2)[0]][] = $route;
        }

        return $byController;
    }
}
