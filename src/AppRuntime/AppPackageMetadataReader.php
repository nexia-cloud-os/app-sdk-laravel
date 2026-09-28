<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

// Reads package App metadata without loading or executing package PHP code.
final class AppPackageMetadataReader
{
    public function read(string $packagePath): AppDefinition
    {
        try {
            return AppDefinition::fromArray($this->appMetadata($this->declaration($packagePath)));
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException('Invalid package app metadata: '.$exception->getMessage(), previous: $exception);
        }
    }

    /** @return array<string, mixed> */
    public function declaration(string $packagePath): array
    {
        $composerPath = is_dir($packagePath)
            ? rtrim($packagePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.json'
            : $packagePath;

        if (! is_file($composerPath)) {
            throw new RuntimeException("Package composer metadata not found at [{$composerPath}].");
        }

        $contents = file_get_contents($composerPath);

        if ($contents === false) {
            throw new RuntimeException("Package composer metadata could not be read at [{$composerPath}].");
        }

        $manifestContents = null;
        $manifestPath = dirname($composerPath).DIRECTORY_SEPARATOR.'nexia.json';
        if (file_exists($manifestPath) || is_link($manifestPath)) {
            if (is_link($manifestPath) || ! is_file($manifestPath) || filesize($manifestPath) > 1048576) {
                throw new RuntimeException('nexia.json must be a regular file of at most 1 MiB.');
            }
            $manifestContents = file_get_contents($manifestPath);
            if ($manifestContents === false) {
                throw new RuntimeException('nexia.json could not be read.');
            }
        }

        return $this->declarationFromJson($contents, $manifestContents);
    }

    /**
     * Resolve immutable package bytes without executing or writing App code.
     * Validates the envelope; callers validate the selected metadata with AppDefinition.
     *
     * @return array<string, mixed>
     */
    public function metadataFromJson(string $composerJson, ?string $manifestJson = null): array
    {
        return $this->appMetadata($this->declarationFromJson($composerJson, $manifestJson));
    }

    /** @return array<string, mixed> */
    public function declarationFromJson(string $composerJson, ?string $manifestJson = null): array
    {
        try {
            $composer = json_decode($composerJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Package composer metadata is invalid JSON.', previous: $exception);
        }
        if (! is_array($composer) || array_is_list($composer)) {
            throw new RuntimeException('Package composer metadata must contain a JSON object.');
        }
        $declaration = $composer['extra']['nexia'] ?? [];
        if (! is_array($declaration)) {
            throw new RuntimeException('Composer extra.nexia must contain an object.');
        }
        if ($manifestJson !== null) {
            if (strlen($manifestJson) > 1048576) {
                throw new RuntimeException('nexia.json must be at most 1 MiB.');
            }
            try {
                $manifest = json_decode($manifestJson, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('nexia.json contains invalid JSON.', previous: $exception);
            }
            if (! is_array($manifest) || array_is_list($manifest)) {
                throw new RuntimeException('nexia.json must contain a JSON object.');
            }
            if (($manifest['schema_version'] ?? null) === '2') {
                if (($manifest['runtime'] ?? null) !== 'laravel') {
                    throw new RuntimeException('Package nexia.json version 2 requires runtime laravel.');
                }
                $legacy = $composer['extra']['nexia'] ?? [];
                if (! is_array($legacy) || array_intersect(array_keys($legacy), ['app', 'core_version', 'test_paths', 'navigation']) !== []) {
                    throw new RuntimeException('Declare Nexia metadata only in nexia.json; remove duplicate extra.nexia declarations.');
                }
                $metadata = $manifest['app'] ?? null;
                if (! is_array($metadata) || array_is_list($metadata)) {
                    throw new RuntimeException('nexia.json must declare an app object.');
                }
                if (array_diff(array_keys($manifest), ['schema_version', 'runtime', 'app', 'core_version', 'test_paths', 'navigation']) !== []) {
                    throw new RuntimeException('Unsupported nexia.json field.');
                }
                if (array_key_exists('navigation', $manifest)) {
                    \Nexia\Navigation\AppNavigation::validate($manifest['navigation'], is_string($metadata['app_key'] ?? null) ? $metadata['app_key'] : '');
                }
                if (array_key_exists('core_version', $manifest)
                    && (! is_string($manifest['core_version']) || trim($manifest['core_version']) === '' || strlen($manifest['core_version']) > 512)) {
                    throw new RuntimeException('nexia.json core_version must be a Composer constraint string.');
                }
                if (array_key_exists('test_paths', $manifest)) {
                    if (! is_array($manifest['test_paths']) || ! array_is_list($manifest['test_paths']) || count($manifest['test_paths']) > 100) {
                        throw new RuntimeException('nexia.json test_paths must be a bounded list.');
                    }
                    foreach ($manifest['test_paths'] as $path) {
                        if (! is_string($path) || strlen($path) > 1024
                            || ! preg_match('~\A[A-Za-z0-9_-][A-Za-z0-9_.-]*(?:/[A-Za-z0-9_-][A-Za-z0-9_.-]*)*\z~D', $path)) {
                            throw new RuntimeException('nexia.json test_paths must stay inside the App.');
                        }
                    }
                }
                $declaration = $manifest;
            } elseif (($manifest['schema_version'] ?? null) !== '1') {
                // Existing browser-preview v1 may accompany a legacy PHP package.
                // Never silently fall back when a new or malformed manifest is present.
                throw new RuntimeException('Unsupported nexia.json schema_version.');
            }
        }

        return $declaration;
    }

    /** @return array<string, mixed> */
    private function appMetadata(array $declaration): array
    {
        $metadata = $declaration['app'] ?? null;
        if (! is_array($metadata) || array_is_list($metadata)) {
            throw new RuntimeException('Package App metadata is missing: declare nexia.json app or legacy extra.nexia.app.');
        }

        return $metadata;
    }
}
