<?php

declare(strict_types=1);

$packageAutoload = dirname(__DIR__).'/vendor/autoload.php';
if (! is_file($packageAutoload)) {
    throw new RuntimeException("Package Composer autoloader not found at [{$packageAutoload}].");
}

require_once $packageAutoload;

spl_autoload_register(static function (string $class): void {
    foreach ([
        'Nexia\\Tests\\' => __DIR__.'/',
        'Nexia\\' => dirname(__DIR__).'/src/',
    ] as $prefix => $root) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $path = $root.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require $path;
        }

        return;
    }
}, true, true);
