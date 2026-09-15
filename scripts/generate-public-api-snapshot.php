#!/usr/bin/env php
<?php

declare(strict_types=1);

function normalized(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
}

function declarationsSource(string $contents): string
{
    $source = '';
    foreach (token_get_all($contents) as $token) {
        if (! is_array($token)) {
            $source .= $token;

            continue;
        }

        [$type, $text] = $token;
        $source .= in_array($type, [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
            ? (preg_replace('/[^\r\n]/', ' ', $text) ?? $text)
            : $text;
    }

    return $source;
}

/** @return list<array<string, mixed>> */
function publicApi(string $root): array
{
    $symbols = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $root.'/src',
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        if ($contents === false) {
            throw new RuntimeException("Could not read [{$file->getPathname()}].");
        }
        $declarations = declarationsSource($contents);
        preg_match('/\bnamespace\s+([^;{]+)\s*[;{]/', $contents, $namespaceMatch);
        $namespace = trim((string) ($namespaceMatch[1] ?? ''));
        preg_match_all(
            '/(?:^|[;}\r\n])\s*(?:(\/\*\*.*?\*\/)\s*)?(?:(?:abstract|final|readonly)\s+)*(class|interface|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m',
            $declarations,
            $definitions,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        foreach ($definitions as $index => $definition) {
            $kind = $definition[2][0];
            $name = $definition[3][0];
            $declarationPrefix = substr($contents, 0, $definition[2][1]);
            preg_match('/\/\*\*.*?\*\/\s*(?:(?:abstract|final|readonly)\s+)*$/s', $declarationPrefix, $docMatch);
            $declarationDoc = $docMatch[0] ?? '';
            $start = $definition[0][1];
            $end = $definitions[$index + 1][0][1] ?? strlen($contents);
            $body = substr($contents, $start, $end - $start);
            $signatures = [];
            $methodPattern = $kind === 'interface'
                ? '/(?:(\/\*\*.*?\*\/)[\s\r\n]*)?(?:public\s+)?(?:static\s+)?function\s+&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\((.*?)\)\s*(?::\s*([^;{]+))?/s'
                : '/(?:(\/\*\*.*?\*\/)[\s\r\n]*)?public\s+(?:static\s+)?function\s+&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\((.*?)\)\s*(?::\s*([^;{]+))?/s';
            preg_match_all($methodPattern, $body, $methods, PREG_SET_ORDER);
            foreach ($methods as $method) {
                $signature = $method[2].'('.normalized($method[3]).')';
                if (trim((string) ($method[4] ?? '')) !== '') {
                    $signature .= ': '.normalized($method[4]);
                }
                $signatures[] = [
                    'signature' => $signature,
                    'deprecated' => str_contains((string) ($method[1] ?? ''), '@deprecated'),
                ];
            }
            usort($signatures, static fn (array $left, array $right): int => $left['signature'] <=> $right['signature']);

            preg_match_all('/\bpublic\s+(?:readonly\s+)?(?:[\\\\A-Za-z_][\\\\A-Za-z0-9_|?]*)\s+\$([A-Za-z_][A-Za-z0-9_]*)/', $body, $properties);
            $dtoKeys = array_values(array_unique($properties[1]));
            sort($dtoKeys, SORT_STRING);
            $enumValues = [];
            if ($kind === 'enum') {
                preg_match_all('/\bcase\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*([\'\"])(.*?)\2\s*;/', $body, $cases, PREG_SET_ORDER);
                foreach ($cases as $case) {
                    $enumValues[$case[1]] = $case[3];
                }
                ksort($enumValues, SORT_STRING);
            }

            $symbols[] = [
                'kind' => $kind,
                'name' => ltrim($namespace.'\\'.$name, '\\'),
                'file' => str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)),
                'deprecated' => str_contains($declarationDoc, '@deprecated'),
                'public_signatures' => $signatures,
                'dto_keys' => $dtoKeys,
                'enum_wire_values' => $enumValues,
            ];
        }
    }

    usort($symbols, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

    return $symbols;
}

/**
 * @param  array{symbols?: list<array{name?: string}>}  $previous
 * @param  array{symbols?: list<array{name?: string}>}  $current
 * @return array{namespaces: list<string>, symbols: list<string>}
 */
function publicApiAdditions(array $previous, array $current): array
{
    $previousSymbols = array_values(array_filter(array_column($previous['symbols'] ?? [], 'name'), 'is_string'));
    $currentSymbols = array_values(array_filter(array_column($current['symbols'] ?? [], 'name'), 'is_string'));
    $symbols = array_values(array_diff($currentSymbols, $previousSymbols));
    $namespace = static fn (string $symbol): string => explode('\\', $symbol)[1] ?? '';
    $previousNamespaces = array_values(array_unique(array_filter(array_map($namespace, $previousSymbols))));
    $currentNamespaces = array_values(array_unique(array_filter(array_map($namespace, $currentSymbols))));
    $namespaces = array_values(array_diff($currentNamespaces, $previousNamespaces));
    sort($symbols, SORT_STRING);
    sort($namespaces, SORT_STRING);

    return ['namespaces' => $namespaces, 'symbols' => $symbols];
}

/** @param array{namespaces: list<string>, symbols: list<string>} $additions */
function publicApiReview(array $additions): string
{
    return 'New top-level namespaces: '.($additions['namespaces'] === [] ? 'none' : implode(', ', $additions['namespaces']))."\n"
        .'New public symbols: '.($additions['symbols'] === [] ? 'none' : implode(', ', $additions['symbols']));
}

/** @param list<string> $arguments */
function run(array $arguments): int
{
    try {
        $root = dirname(__DIR__);
        $snapshot = [
            'version' => 1,
            'source' => 'src',
            'symbols' => publicApi($root),
        ];
        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        $path = $root.'/governance/php-public-api-snapshot.json';
        $mode = $arguments[1] ?? '--check';
        $previous = is_file($path)
            ? json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR)
            : ['symbols' => []];
        $review = publicApiReview(publicApiAdditions($previous, $snapshot));

        if ($mode === '--write') {
            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0777, true) && ! is_dir(dirname($path))) {
                throw new RuntimeException('Could not create SDK governance directory.');
            }
            file_put_contents($path, $json);
            fwrite(STDOUT, $review."\n");
            fwrite(STDOUT, 'Wrote public API snapshot with '.count($snapshot['symbols'])." symbols.\n");

            return 0;
        }
        if ($mode !== '--check') {
            throw new RuntimeException('Usage: scripts/generate-public-api-snapshot.php [--check|--write]');
        }
        if (! is_file($path) || file_get_contents($path) !== $json) {
            throw new RuntimeException($review."\nPublic API snapshot is stale. Run scripts/generate-public-api-snapshot.php --write.");
        }
        fwrite(STDOUT, $review."\n");
        fwrite(STDOUT, 'Public API snapshot is current with '.count($snapshot['symbols'])." symbols.\n");

        return 0;
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage()."\n");

        return 1;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(run($argv));
}
