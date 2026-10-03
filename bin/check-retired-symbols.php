#!/usr/bin/env php
<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$manifestPath = $packageRoot.'/contract/retired-symbols.json';
$manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
$roots = array_slice($argv, 1);
if ($roots === []) {
    $roots = [$packageRoot.'/src', $packageRoot.'/tests'];
}

$extensions = ['php', 'ts', 'tsx', 'js', 'jsx'];
$files = [];
foreach ($roots as $root) {
    $path = str_starts_with($root, '/') ? $root : getcwd().'/'.$root;
    if (is_file($path)) {
        $files[] = $path;
        continue;
    }
    if (! is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $entry): bool {
                if (! $entry->isDir()) {
                    return true;
                }

                return ! in_array($entry->getFilename(), ['.git', 'vendor', 'node_modules', 'docs', 'contract', 'bin'], true);
            },
        ),
    );
    foreach ($iterator as $entry) {
        if ($entry instanceof SplFileInfo && $entry->isFile()) {
            $files[] = $entry->getPathname();
        }
    }
}

$violations = [];
foreach (array_unique($files) as $file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (! in_array($extension, $extensions, true)) {
        continue;
    }
    $contents = file_get_contents($file);
    if (! is_string($contents)) {
        continue;
    }

    $phpCode = $contents;
    if ($extension === 'php') {
        $phpCode = '';
        foreach (token_get_all($contents) as $token) {
            $phpCode .= is_array($token)
                ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML], true) ? ' ' : $token[1])
                : $token;
        }
    }

    $symbols = $extension === 'php' ? ($manifest['php'] ?? []) : ($manifest['frontend'] ?? []);
    foreach ($symbols as $symbol) {
        if (! is_string($symbol) || $symbol === '') {
            continue;
        }
        $needles = [$symbol];
        if ($extension === 'php') {
            $needles[] = substr($symbol, (int) strrpos($symbol, '\\') + 1);
        }
        foreach (array_unique($needles) as $needle) {
            $matches = $needle === $symbol
                ? str_contains($contents, $needle)
                : preg_match('/(?<![A-Za-z0-9_])'.preg_quote($needle, '/').'(?![A-Za-z0-9_])/', $phpCode) === 1;
            if ($matches) {
                $violations[] = $file.': retired SDK symbol '.$symbol;
                break;
            }
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "retired-sdk-symbols: failure\n".implode("\n", $violations)."\n");
    exit(1);
}

fwrite(STDOUT, "retired-sdk-symbols: clean\n");
