<?php

declare(strict_types=1);

require __DIR__.'/register-source-autoload.php';

/** @return list<string> */
function sourceSymbols(): array
{
    $symbols = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        dirname(__DIR__).'/src',
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $source = (string) file_get_contents($file->getPathname());
        preg_match('/^namespace\s+([^;]+);/m', $source, $namespace);
        if (! isset($namespace[1])) {
            continue;
        }

        $tokens = token_get_all($source);
        foreach ($tokens as $index => $token) {
            if (! is_array($token) || ! in_array($token[0], [T_CLASS, T_INTERFACE, T_ENUM, T_TRAIT], true)) {
                continue;
            }

            for ($cursor = $index + 1; isset($tokens[$cursor]); $cursor++) {
                if (is_array($tokens[$cursor]) && in_array($tokens[$cursor][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                if (is_array($tokens[$cursor]) && $tokens[$cursor][0] === T_STRING) {
                    $symbols[] = $namespace[1].'\\'.$tokens[$cursor][1];
                }

                break;
            }
        }
    }

    return $symbols;
}

/** @return list<string> */
function namedTypes(?ReflectionType $type): array
{
    if ($type === null) {
        return [];
    }

    if ($type instanceof ReflectionNamedType) {
        return [$type->getName()];
    }

    if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
        return array_merge(...array_map(namedTypes(...), $type->getTypes()));
    }

    return [];
}

function isBuiltinOrRelative(string $type): bool
{
    return in_array(strtolower($type), [
        'array', 'bool', 'callable', 'false', 'float', 'int', 'iterable', 'mixed',
        'never', 'null', 'object', 'parent', 'self', 'static', 'string', 'true', 'void',
    ], true);
}

function isSdkDeclarationFile(string|false $path): bool
{
    return is_string($path) && str_starts_with($path, dirname(__DIR__).'/src/');
}

/** @param list<string> $failures */
function assertResolvableType(string $type, string $context, array &$failures): void
{
    if (isBuiltinOrRelative($type)) {
        return;
    }

    if (! class_exists($type) && ! interface_exists($type) && ! trait_exists($type) && ! enum_exists($type)) {
        $failures[] = "{$context} references unresolved type {$type}";
    }
}

$failures = [];
$symbols = array_values(array_unique(sourceSymbols()));

foreach ($symbols as $symbol) {
    if (! class_exists($symbol) && ! interface_exists($symbol) && ! trait_exists($symbol) && ! enum_exists($symbol)) {
        $failures[] = "SDK symbol {$symbol} does not autoload";

        continue;
    }

    $reflection = new ReflectionClass($symbol);
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $context = "{$symbol}::{$method->getName()}";
        $types = namedTypes($method->getReturnType());
        foreach ($method->getParameters() as $parameter) {
            $types = [...$types, ...namedTypes($parameter->getType())];
        }
        foreach ($types as $type) {
            if (! isSdkDeclarationFile($method->getFileName()) && ! str_starts_with($type, 'Nexia\\')) {
                continue;
            }
            assertResolvableType($type, $context, $failures);
        }
    }

    foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        foreach (namedTypes($property->getType()) as $type) {
            if (! isSdkDeclarationFile($property->getDeclaringClass()->getFileName()) && ! str_starts_with($type, 'Nexia\\')) {
                continue;
            }
            assertResolvableType($type, "{$symbol}::\${$property->getName()}", $failures);
        }
    }
}

if ($failures !== []) {
    throw new RuntimeException("Namespace type resolution failed:\n".implode("\n", $failures));
}

echo 'Namespace type resolution passed for '.count($symbols)." SDK declarations.\n";
