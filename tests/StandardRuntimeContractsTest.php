<?php

declare(strict_types=1);

use Nexia\Actions\ActionExecutionContract;
use Nexia\AppRuntime\RuntimeDependencyPolicy;
use Nexia\AppRuntime\RuntimeRequirements;
use Nexia\Settings\SettingDefinition;

require dirname(__DIR__).'/vendor/autoload.php';

$rejects = static function (Closure $operation): void {
    try {
        $operation();
    } catch (InvalidArgumentException|TypeError) {
        return;
    }
    throw new RuntimeException('Unsupported declaration was accepted.');
};
$requirements = ['schema_version' => 1, 'runtime' => RuntimeRequirements::RUNTIME,
    'contracts' => RuntimeRequirements::CONTRACTS, 'required_capabilities' => RuntimeRequirements::CAPABILITIES,
    'optional_capabilities' => [], 'integrations' => []];
assert(RuntimeRequirements::validate($requirements, 'sample') === $requirements);
// Shared metadata parsing must not choose the sandbox host for a Composer host.
foreach ([['runtime' => 'another-runtime'], ['contracts' => ['catalog' => 2]],
    ['required_capabilities' => ['jobs.execute']], ['integrations' => [
        ['app' => 'other', 'kind' => 'event', 'key' => 'other.changed', 'version' => '1.0', 'required' => true],
    ]]] as $unsupported) {
    $declaration = [...$requirements, ...$unsupported];
    if (RuntimeRequirements::validateDeclaration($declaration, 'sample') !== $declaration) {
        throw new RuntimeException('Declaration parsing changed host requirements.');
    }
    $manifest = ['schema_version' => '2', 'runtime' => 'laravel',
        'app' => ['app_key' => 'sample'], 'platform' => $declaration];
    $parsed = (new \Nexia\AppRuntime\AppPackageMetadataReader)->declarationFromJson(
        '{"name":"example/sample"}', json_encode($manifest, JSON_THROW_ON_ERROR),
    );
    if ($parsed['platform'] !== $declaration) {
        throw new RuntimeException('Shared metadata reader applied sandbox support policy.');
    }
    $rejects(fn () => RuntimeRequirements::validate($declaration, 'sample'));
}
$rejects(fn () => RuntimeRequirements::validateDeclaration([...$requirements, 'contracts' => ['catalog' => '1']], 'sample'));
$rejects(fn () => RuntimeRequirements::validateDeclaration([...$requirements, 'runtime' => null], 'sample'));

$rejects(fn () => RuntimeRequirements::validate([...$requirements, 'required_capabilities' => ['jobs.execute']], 'sample'));
$rejects(fn () => RuntimeRequirements::validate([...$requirements, 'runtime' => 'other'], 'sample'));
$rejects(fn () => RuntimeRequirements::validate([...$requirements, 'contracts' => ['catalog' => 2]], 'sample'));
$rejects(fn () => RuntimeRequirements::validate([...$requirements, 'integrations' => [
    ['app' => 'other', 'kind' => 'event', 'key' => 'other.changed', 'version' => '1.0', 'required' => true],
]], 'sample'));
$app = ['require' => ['nexia-cloud-os/sdk-laravel' => '^0.7.0'], 'autoload' => ['psr-4' => ['Nexia\\Apps\\Example\\Sample\\' => 'src/']]];
$lock = ['packages' => [['name' => 'nexia-cloud-os/sdk-laravel', 'version' => '0.7.0']],
    'packages-dev' => [['name' => 'pestphp/pest', 'version' => '4.7.8']]];
RuntimeDependencyPolicy::validate($app, $lock);
$renamedLock = $lock;
$renamedLock['packages'][0]['replace'] = ['nexia/sdk-laravel' => 'self.version', 'amuzcorp/nexia-app-sdk-laravel' => 'self.version'];
foreach (array_keys($renamedLock['packages'][0]['replace']) as $oldName) {
    $legacyApp = [...$app, 'require' => [$oldName => '^0.7.0']];
    RuntimeDependencyPolicy::validate($legacyApp, $renamedLock);
    $rejects(fn () => RuntimeDependencyPolicy::validate([...$legacyApp, 'require' => [$oldName => '^0.6.0']], $renamedLock));
    $rejects(fn () => RuntimeDependencyPolicy::validate($legacyApp, $lock));
    $untrustedLock = $renamedLock;
    $untrustedLock['packages'][0]['name'] = 'example/fake-sdk';
    $rejects(fn () => RuntimeDependencyPolicy::validate($legacyApp, $untrustedLock));
    $wildcardLock = $renamedLock;
    $wildcardLock['packages'][0]['replace'][$oldName] = '*';
    $rejects(fn () => RuntimeDependencyPolicy::validate($legacyApp, $wildcardLock));
}
$rejects(fn () => RuntimeDependencyPolicy::validate([...$app, 'require' => [...$app['require'], 'example/unapproved' => '*']], $lock));
$rejects(fn () => RuntimeDependencyPolicy::validate([...$app, 'require' => ['nexia-cloud-os/sdk-laravel' => '^0.6.0']], $lock));
$rejects(fn () => RuntimeDependencyPolicy::validate([...$app, 'require' => [...$app['require'], 'pestphp/pest' => '^4.0']], $lock));
RuntimeDependencyPolicy::validate([...$app, 'require-dev' => ['pestphp/pest' => '^4.0']], $lock, true);
$rejects(fn () => RuntimeDependencyPolicy::validate([...$app, 'require-dev' => ['example/unapproved' => '*']], $lock, true));
$rejects(fn () => RuntimeDependencyPolicy::validate([...$app, 'autoload' => ['files' => ['bootstrap.php']]], $lock));
$secret = new SettingDefinition('sample.token', 'app', 'string', 'Backend token.', 'sample.settings.manage', secret: true);
SettingDefinition::validateCatalog([$secret->toArray()], 'sample', ['sample.settings.manage']);
$rejects(fn () => new SettingDefinition('sample.token', 'app', 'string', 'Token.', 'sample.settings.manage', secret: true, default: 'SENTINEL'));
$rejects(fn () => new SettingDefinition('sample.token', 'app', 'string', 'Token.', 'sample.settings.manage', secret: true, frontend: true));
$rejects(fn () => SettingDefinition::validateCatalog([$secret->toArray(), $secret->toArray()], 'sample', ['sample.settings.manage']));
$rejects(fn () => SettingDefinition::validateCatalog([$secret->toArray()], 'other', ['sample.settings.manage']));
$rejects(fn () => SettingDefinition::validateCatalog([[...$secret->toArray(), 'value' => 'SENTINEL']], 'sample', ['sample.settings.manage']));
assert(! str_contains(json_encode($secret->toArray(), JSON_THROW_ON_ERROR), 'SENTINEL'));
$action = new ActionExecutionContract;
assert(ActionExecutionContract::fromArray($action->toArray())->toArray() === $action->toArray());
$rejects(fn () => new ActionExecutionContract(available: true));
$catalog = ['app_key' => 'sample', 'requirements' => $requirements,
    'permissions' => [['key' => 'sample.note.update', 'lifecycle' => 'active']],
    'resources' => [['key' => 'sample.note']],
    'actions' => [['key' => 'sample.note.complete', 'resource_key' => 'sample.note', 'definition' => [
        'permission' => 'sample.note.update', 'method' => 'POST', 'path' => '/api/sample/notes/{note}/complete',
    ]]],
    'route_bindings' => [['key' => 'sample.note.complete', 'operation' => 'action', 'method' => 'POST', 'path' => '/api/sample/notes/{note}/complete']]];
\Nexia\AppRuntime\StandardCatalogPolicy::validate($catalog);
foreach ([['route_bindings' => []], ['permissions' => []], ['resources' => []],
    ['route_bindings' => [$catalog['route_bindings'][0], $catalog['route_bindings'][0]]]] as $invalid) {
    try {
        \Nexia\AppRuntime\StandardCatalogPolicy::validate([...$catalog, ...$invalid]);
        throw new RuntimeException('Invalid Catalog reference was accepted.');
    } catch (\Nexia\AppRuntime\CatalogValidationException) {
    }
}
echo "Standard Runtime declaration and dependency policies passed.\n";
