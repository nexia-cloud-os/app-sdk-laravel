<?php

declare(strict_types=1);

$kernel = dirname(__DIR__).'/src';
$violations = [];

/** @param list<string> $names */
function callsRuntimeGlobal(string $source, array $names): bool
{
    $tokens = token_get_all($source);
    $previous = null;

    foreach ($tokens as $index => $token) {
        if (! is_array($token)) {
            if (! in_array($token, [T_WHITESPACE], true)) {
                $previous = $token;
            }

            continue;
        }

        if ($token[0] !== T_STRING || ! in_array($token[1], $names, true)) {
            if (! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $previous = $token[0];
            }

            continue;
        }

        $next = $tokens[$index + 1] ?? null;
        while (is_array($next) && $next[0] === T_WHITESPACE) {
            $next = $tokens[++$index + 1] ?? null;
        }

        if ($next === '(' && ! in_array($previous, [T_FUNCTION, T_DOUBLE_COLON, T_OBJECT_OPERATOR], true)) {
            return true;
        }

        $previous = $token[0];
    }

    return false;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($kernel, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! str_ends_with($file->getFilename(), '.php')) {
        continue;
    }

    $path = $file->getPathname();
    if (str_starts_with($path, $kernel.'/Laravel/')) {
        continue;
    }

    $source = file_get_contents($path);
    if ($source === false) {
        throw new RuntimeException("Unable to read kernel file [{$path}].");
    }

    if (preg_match('/^use\\s+(?:App|Illuminate|Laravel|Spatie)\\\\/m', $source) === 1
        || preg_match('/^use\\s+Amuzcorp\\\\Nexia\\\\/m', $source) === 1
        || preg_match('/(?<![A-Za-z0-9_])\\\\App\\\\[A-Za-z_]/', $source) === 1
        || preg_match('/(?<![A-Za-z0-9_])\\\\Amuzcorp\\\\Nexia\\\\[A-Za-z_]/', $source) === 1
        || callsRuntimeGlobal($source, ['app', 'auth', 'tenant', 'request', 'now'])) {
        $violations[] = str_replace($kernel.'/', '', $path);
    }
}

if ($violations !== []) {
    throw new RuntimeException(
        'Contract kernel imports Core/App/framework code or calls runtime globals: '.implode(', ', $violations),
    );
}

echo "Kernel import boundary passed.\n";
