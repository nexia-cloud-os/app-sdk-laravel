<?php

declare(strict_types=1);

use Nexia\AppRuntime\AppPackageMetadataReader;

require dirname(__DIR__).'/vendor/autoload.php';

$packagePath = sys_get_temp_dir().'/nexia-sdk-app-metadata-'.bin2hex(random_bytes(6));

if (! mkdir($packagePath, 0777, true) && ! is_dir($packagePath)) {
    throw new RuntimeException("Could not create test directory [{$packagePath}].");
}

$writeComposer = static function (mixed $composer) use ($packagePath): void {
    $contents = is_string($composer)
        ? $composer
        : json_encode($composer, JSON_THROW_ON_ERROR);

    if (file_put_contents($packagePath.'/composer.json', $contents) === false) {
        throw new RuntimeException('Could not write test Composer metadata.');
    }
};

try {
    $writeComposer([
        'name' => 'amuzcorp/nexia-sample-addon',
        'extra' => ['nexia' => ['app' => [
            'manifest' => 'Tests\\Fixtures\\SampleAddon\\SampleAddonAppManifest',
            'app_family' => 'sample',
            'app_icon' => 'box',
            'launcher_order' => 10,
            'overview_navigation_id' => 'sample-addon',
            'app_name' => 'Sample Addon',
            'app_key' => 'sample-addon',
            'app_table_prefix' => 'sample_addon',
            'prerequisite_apps' => ['sample-base'],
        ]]],
    ]);

    $definition = (new AppPackageMetadataReader)->read($packagePath);
    if ($definition->appKey !== 'sample-addon' || $definition->prerequisiteApps !== ['sample-base']) {
        throw new RuntimeException('Canonical Composer App metadata was not read correctly.');
    }

    $invalidCases = [
        [['name' => 'amuzcorp/nexia-invalid'], 'extra.nexia.app'],
        ['"not-an-object"', 'must contain a JSON object'],
        [['extra' => ['nexia' => ['app' => [
            'manifest' => 'Example\\ExampleAppManifest',
            'app_family' => 'example',
            'app_icon' => 'box',
            'launcher_order' => 10,
            'overview_navigation_id' => 'example',
            'app_name' => 'Example',
            'app_key' => 'example',
            'app_table_prefix' => 'example',
        ]]]], 'prerequisite_apps'],
    ];

    foreach ($invalidCases as [$composer, $message]) {
        $writeComposer($composer);

        try {
            (new AppPackageMetadataReader)->read($packagePath);
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), $message)) {
                continue;
            }

            throw $exception;
        }

        throw new RuntimeException("Invalid Composer App metadata was accepted; expected [{$message}].");
    }
} finally {
    $composerPath = $packagePath.'/composer.json';
    if (is_file($composerPath)) {
        unlink($composerPath);
    }
    if (is_dir($packagePath)) {
        rmdir($packagePath);
    }
}

fwrite(STDOUT, "App package metadata reader contract passed.\n");
