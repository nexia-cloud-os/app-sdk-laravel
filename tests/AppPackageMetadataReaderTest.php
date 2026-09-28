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

    $native = ['schema_version' => '2', 'runtime' => 'laravel', 'app' => $definition->toArray()];
    if ((new AppPackageMetadataReader)->metadataFromJson('{"name":"example/sample"}', json_encode($native, JSON_THROW_ON_ERROR)) !== $definition->toArray()) {
        throw new RuntimeException('Immutable JSON metadata resolution differs from file resolution.');
    }
    $writeComposer(['name' => 'example/sample']);
    file_put_contents($packagePath.'/nexia.json', json_encode($native, JSON_THROW_ON_ERROR));
    foreach ([$packagePath, $packagePath.'/composer.json'] as $input) {
        if ((new AppPackageMetadataReader)->read($input)->toArray() !== $definition->toArray()) {
            throw new RuntimeException('Native nexia.json metadata was not read correctly.');
        }
    }
    $declaration = [...$native, 'core_version' => '^0.6.1', 'test_paths' => ['tests', 'tests/Feature']];
    file_put_contents($packagePath.'/nexia.json', json_encode($declaration, JSON_THROW_ON_ERROR));
    $reader = new AppPackageMetadataReader;
    assert($reader->declaration($packagePath) === $declaration);
    assert($reader->read($packagePath)->appKey === $definition->appKey);
    foreach ([['core_version' => null], ['core_version' => ''], ['test_paths' => 'tests'],
        ['test_paths' => ['../tests']], ['test_paths' => ['/tests']], ['test_paths' => ['tests/../other']],
        ['test_paths' => ['tests\\other']], ['unknown' => true]] as $invalidFields) {
        $rejected = false;
        try {
            $reader->declarationFromJson('{"name":"example/sample"}', json_encode([...$declaration, ...$invalidFields], JSON_THROW_ON_ERROR));
        } catch (RuntimeException) {
            $rejected = true;
        }
        assert($rejected, 'Invalid native declarations must fail.');
    }
    foreach (['app', 'core_version', 'test_paths', 'navigation'] as $duplicate) {
        $rejected = false;
        try {
            $reader->declarationFromJson(json_encode(['extra' => ['nexia' => [$duplicate => null]]], JSON_THROW_ON_ERROR), json_encode($declaration, JSON_THROW_ON_ERROR));
        } catch (RuntimeException) {
            $rejected = true;
        }
        assert($rejected, 'Legacy duplicate fields must fail even when null.');
    }
    $legacyComposer = ['extra' => ['nexia' => ['app' => $definition->toArray()]]];
    $writeComposer($legacyComposer);
    foreach ([$native, ['schema_version' => '3'], ['schema_version' => '2', 'runtime' => 'browser'],
        ['schema_version' => 2], [], '{invalid'] as $invalid) {
        file_put_contents($packagePath.'/nexia.json', is_string($invalid) ? $invalid : json_encode($invalid, JSON_THROW_ON_ERROR));
        $rejected = false;
        try {
            (new AppPackageMetadataReader)->read($packagePath);
        } catch (RuntimeException) {
            $rejected = true;
        }
        if (! $rejected) {
            throw new RuntimeException('Invalid or duplicate manifest silently fell back to Composer.');
        }
    }
    file_put_contents($packagePath.'/nexia.json', json_encode(['schema_version' => '1', 'app' => ['id' => 'dev.sample']]));
    if ((new AppPackageMetadataReader)->read($packagePath)->appKey !== $definition->appKey) {
        throw new RuntimeException('Legacy browser-preview coexistence was broken.');
    }
    unlink($packagePath.'/nexia.json');
    symlink($packagePath.'/composer.json', $packagePath.'/nexia.json');
    $rejected = false;
    try {
        (new AppPackageMetadataReader)->read($packagePath);
    } catch (RuntimeException) {
        $rejected = true;
    }
    if (! $rejected) {
        throw new RuntimeException('Linked manifest was accepted.');
    }
    unlink($packagePath.'/nexia.json');

    $writeComposer(['name' => 'example/sample']);
    foreach ([
        ['schema_version' => '2', 'runtime' => 'laravel'],
        ['schema_version' => '2', 'runtime' => 'laravel', 'app' => []],
        ['schema_version' => '2', 'runtime' => 'laravel', 'app' => ['app_key' => 'missing-fields']],
        str_repeat(' ', 1048577),
    ] as $invalid) {
        file_put_contents($packagePath.'/nexia.json', is_string($invalid) ? $invalid : json_encode($invalid, JSON_THROW_ON_ERROR));
        clearstatcache();
        $rejected = false;
        try {
            (new AppPackageMetadataReader)->read($packagePath);
        } catch (RuntimeException) {
            $rejected = true;
        }
        if (! $rejected) {
            throw new RuntimeException('Incomplete or oversized native manifest was accepted.');
        }
    }
    unlink($packagePath.'/nexia.json');

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
    if (is_file($packagePath.'/nexia.json') || is_link($packagePath.'/nexia.json')) {
        unlink($packagePath.'/nexia.json');
    }
    $composerPath = $packagePath.'/composer.json';
    if (is_file($composerPath)) {
        unlink($composerPath);
    }
    if (is_dir($packagePath)) {
        rmdir($packagePath);
    }
}

fwrite(STDOUT, "App package metadata reader contract passed.\n");
