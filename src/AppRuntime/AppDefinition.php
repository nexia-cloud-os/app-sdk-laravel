<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use InvalidArgumentException;

/**
 * Immutable identity and installation metadata for one package app.
 */
final readonly class AppDefinition
{
    public string $appIcon;

    public int $launcherOrder;

    public string $overviewNavigationId;

    /** @var list<string> */
    public const RESERVED_APP_KEYS = [
        'core',
        'host',
        'platform',
        'shell',
        'tenant',
        'process',
        'apps',
    ];

    public const READINESS_AVAILABLE = 'available';

    public const READINESS_PREVIEW = 'preview';

    public const READINESS_BETA = 'beta';

    /** @var list<string> */
    public const READINESS_STATES = [
        self::READINESS_AVAILABLE,
        self::READINESS_BETA,
        self::READINESS_PREVIEW,
    ];

    /**
     * @param  list<string>  $prerequisiteApps
     * @param  array<string, string>  $descriptions
     */
    public function __construct(
        public string $manifestClass,
        public string $appFamily,
        public string $appName,
        public string $appKey,
        public string $appTablePrefix,
        public array $prerequisiteApps = [],
        public array $descriptions = [],
        public string $readiness = self::READINESS_AVAILABLE,
        string $appIcon = 'box',
        int $launcherOrder = 500,
        ?string $overviewNavigationId = null,
    ) {
        $this->appIcon = $appIcon;
        $this->launcherOrder = $launcherOrder;
        $this->overviewNavigationId = $overviewNavigationId ?? $appKey;

        if (preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)+[A-Za-z_][A-Za-z0-9_]*$/', $manifestClass) !== 1) {
            throw new InvalidArgumentException(
                'App metadata [manifest] must be a fully qualified PHP class name.',
            );
        }

        $this->assertNotBlank($appName, 'app_name');
        $this->assertKebabKey($appFamily, 'app_family');
        $this->assertNotBlank($this->appIcon, 'app_icon');
        $this->assertNavigationId($this->overviewNavigationId, 'overview_navigation_id');
        $this->assertKebabKey($appKey, 'app_key');

        if ($this->launcherOrder < 0) {
            throw new InvalidArgumentException(
                'App metadata [launcher_order] must be zero or greater.',
            );
        }

        if (self::isReservedAppKey($appKey)) {
            throw new InvalidArgumentException(
                "App metadata [app_key] cannot use reserved Core key [{$appKey}].",
            );
        }

        if (preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $appTablePrefix) !== 1) {
            throw new InvalidArgumentException(
                'App metadata [app_table_prefix] must be lower snake_case.',
            );
        }

        $seen = [];

        foreach ($prerequisiteApps as $prerequisite) {
            if (! is_string($prerequisite)) {
                throw new InvalidArgumentException(
                    'App metadata [prerequisite_apps] must contain only app keys.',
                );
            }

            $this->assertKebabKey($prerequisite, 'prerequisite_apps');

            if ($prerequisite === $appKey) {
                throw new InvalidArgumentException(
                    "App [{$appKey}] cannot require itself as a prerequisite.",
                );
            }

            if (isset($seen[$prerequisite])) {
                throw new InvalidArgumentException(
                    "App [{$appKey}] declares prerequisite [{$prerequisite}] more than once.",
                );
            }

            $seen[$prerequisite] = true;
        }

        foreach ($descriptions as $locale => $description) {
            if (! is_string($locale) || trim($locale) === '' || ! is_string($description)) {
                throw new InvalidArgumentException(
                    'App metadata [descriptions] must map locale keys to description strings.',
                );
            }
        }

        if (! in_array($readiness, self::READINESS_STATES, true)) {
            throw new InvalidArgumentException(
                'App metadata [readiness] must be one of: '.implode(', ', self::READINESS_STATES).'.',
            );
        }
    }

    public function isInstallable(): bool
    {
        return in_array($this->readiness, [self::READINESS_AVAILABLE, self::READINESS_BETA], true);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function fromArray(array $metadata): self
    {
        $expectedFields = [
            'manifest',
            'app_family',
            'app_icon',
            'launcher_order',
            'overview_navigation_id',
            'app_name',
            'app_key',
            'app_table_prefix',
            'prerequisite_apps',
            'descriptions',
            'readiness',
        ];
        $unknownFields = array_values(array_diff(array_keys($metadata), $expectedFields));

        if ($unknownFields !== []) {
            throw new InvalidArgumentException(
                'Unknown app metadata field(s): '.implode(', ', $unknownFields).'.',
            );
        }

        foreach (['manifest', 'app_family', 'app_icon', 'overview_navigation_id', 'app_name', 'app_key', 'app_table_prefix'] as $field) {
            if (! isset($metadata[$field]) || ! is_string($metadata[$field])) {
                throw new InvalidArgumentException(
                    "App metadata [{$field}] must be a non-empty string.",
                );
            }
        }

        if (! isset($metadata['launcher_order']) || ! is_int($metadata['launcher_order'])) {
            throw new InvalidArgumentException(
                'App metadata [launcher_order] must be an integer.',
            );
        }

        $prerequisites = $metadata['prerequisite_apps'] ?? null;

        if (! is_array($prerequisites) || ! array_is_list($prerequisites)) {
            throw new InvalidArgumentException(
                'App metadata [prerequisite_apps] must be a list of app keys.',
            );
        }

        $descriptions = $metadata['descriptions'] ?? [];

        if (! is_array($descriptions) || (array_is_list($descriptions) && $descriptions !== [])) {
            throw new InvalidArgumentException(
                'App metadata [descriptions] must map locale keys to description strings.',
            );
        }

        $readiness = $metadata['readiness'] ?? self::READINESS_AVAILABLE;

        if (! is_string($readiness)) {
            throw new InvalidArgumentException(
                'App metadata [readiness] must be one of: '.implode(', ', self::READINESS_STATES).'.',
            );
        }

        return new self(
            manifestClass: $metadata['manifest'],
            appFamily: $metadata['app_family'],
            appIcon: $metadata['app_icon'],
            launcherOrder: $metadata['launcher_order'],
            overviewNavigationId: $metadata['overview_navigation_id'],
            appName: $metadata['app_name'],
            appKey: $metadata['app_key'],
            appTablePrefix: $metadata['app_table_prefix'],
            prerequisiteApps: $prerequisites,
            descriptions: $descriptions,
            readiness: $readiness,
        );
    }

    /**
     * @return array{
     *     manifest: string,
     *     app_family: string,
     *     app_icon: string,
     *     launcher_order: int,
     *     overview_navigation_id: string,
     *     app_name: string,
     *     app_key: string,
     *     app_table_prefix: string,
     *     prerequisite_apps: list<string>,
     *     descriptions: array<string, string>,
     *     readiness: string
     * }
     */
    public function toArray(): array
    {
        return [
            'manifest' => $this->manifestClass,
            'app_family' => $this->appFamily,
            'app_icon' => $this->appIcon,
            'launcher_order' => $this->launcherOrder,
            'overview_navigation_id' => $this->overviewNavigationId,
            'app_name' => $this->appName,
            'app_key' => $this->appKey,
            'app_table_prefix' => $this->appTablePrefix,
            'prerequisite_apps' => $this->prerequisiteApps,
            'descriptions' => $this->descriptions,
            'readiness' => $this->readiness,
        ];
    }

    public static function isReservedAppKey(string $appKey): bool
    {
        return in_array($appKey, self::RESERVED_APP_KEYS, true);
    }

    private function assertNotBlank(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                "App metadata [{$field}] must be a non-empty string.",
            );
        }
    }

    private function assertKebabKey(string $value, string $field): void
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/', $value) !== 1) {
            throw new InvalidArgumentException(
                "App metadata [{$field}] must be lower kebab-case.",
            );
        }
    }

    private function assertNavigationId(string $value, string $field): void
    {
        if (preg_match('/^[a-z][a-z0-9]*(?:[.-][a-z0-9]+)*$/', $value) !== 1) {
            throw new InvalidArgumentException(
                "App metadata [{$field}] must be a dot- or kebab-delimited navigation id.",
            );
        }
    }
}
