<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

/** Reads package App metadata without loading or executing package PHP code. */
final class AppPackageMetadataReader
{
    public function read(string $packagePath): AppDefinition
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

        try {
            $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "Package composer metadata is invalid JSON at [{$composerPath}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! is_array($composer)) {
            throw new RuntimeException(
                "Package composer metadata must contain a JSON object at [{$composerPath}].",
            );
        }

        $metadata = $composer['extra']['nexia']['app'] ?? null;

        if (! is_array($metadata)) {
            throw new RuntimeException(
                "Package composer metadata [extra.nexia.app] is missing at [{$composerPath}].",
            );
        }

        try {
            return AppDefinition::fromArray($metadata);
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException(
                "Invalid package app metadata at [{$composerPath}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }
}
