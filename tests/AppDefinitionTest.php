<?php

declare(strict_types=1);

use Nexia\AppRuntime\AppDefinition;

require dirname(__DIR__).'/vendor/autoload.php';

$definition = AppDefinition::fromArray([
    'manifest' => 'Acme\\Sample\\SampleManifest',
    'app_family' => 'finance',
    'app_icon' => 'receipt',
    'launcher_order' => 20,
    'overview_navigation_id' => 'sample.overview',
    'app_name' => 'Sample',
    'app_key' => 'sample',
    'app_table_prefix' => 'expense',
    'prerequisite_apps' => ['sample-owner'],
    'descriptions' => ['en' => 'Sample'],
    'readiness' => AppDefinition::READINESS_AVAILABLE,
]);

if (! $definition->isInstallable() || $definition->toArray()['app_key'] !== 'sample') {
    throw new RuntimeException('App definition round-trip contract changed unexpectedly.');
}

$beta = new AppDefinition(
    manifestClass: 'Acme\\Sample\\BetaManifest',
    appFamily: 'finance',
    appName: 'Beta',
    appKey: 'beta',
    appTablePrefix: 'beta',
    readiness: AppDefinition::READINESS_BETA,
);

if (! $beta->isInstallable()) {
    throw new RuntimeException('Beta App definitions must be installable.');
}

$invalid = [
    ['app_key' => 'core'],
    ['app_key' => 'bad_key'],
    ['prerequisite_apps' => ['sample']],
    ['prerequisite_apps' => ['sample-owner', 'sample-owner']],
    ['readiness' => 'unknown'],
];

foreach ($invalid as $override) {
    try {
        new AppDefinition(
            manifestClass: 'Acme\\Sample\\SampleManifest',
            appFamily: 'finance',
            appName: 'Sample',
            appKey: $override['app_key'] ?? 'sample',
            appTablePrefix: 'expense',
            prerequisiteApps: $override['prerequisite_apps'] ?? [],
            readiness: $override['readiness'] ?? AppDefinition::READINESS_AVAILABLE,
        );
    } catch (InvalidArgumentException) {
        continue;
    }

    throw new RuntimeException('Invalid app definition was accepted.');
}

fwrite(STDOUT, "App definition contract passed.\n");
