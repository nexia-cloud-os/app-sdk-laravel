<?php

declare(strict_types=1);

namespace Nexia\Settings;

use InvalidArgumentException;

/** Definitions only. Secret values and secret defaults never belong in a declaration. */
final readonly class SettingDefinition
{
    public function __construct(
        public string $key,
        public string $scope,
        public string $type,
        public string $description,
        public string $managementPermission,
        public bool $required = false,
        public bool $secret = false,
        public bool $frontend = false,
        public string|int|float|bool|null $default = null,
    ) {
        if (! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $key)
            || ! in_array($scope, ['app', 'tenant'], true)
            || ! in_array($type, ['string', 'integer', 'number', 'boolean'], true)
            || trim($description) === '' || strlen($description) > 2000
            || ! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $managementPermission)) {
            throw new InvalidArgumentException('Invalid setting definition.');
        }
        if ($secret && ($frontend || $default !== null)) {
            throw new InvalidArgumentException('Secret settings are Backend-only and cannot declare defaults.');
        }
        if ($default !== null && ! match ($type) {
            'string' => is_string($default) && strlen($default) <= 4096,
            'integer' => is_int($default),
            'number' => (is_int($default) || is_float($default)) && is_finite((float) $default),
            'boolean' => is_bool($default),
        }) {
            throw new InvalidArgumentException('Setting default does not match its declared type.');
        }
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'scope' => $this->scope, 'type' => $this->type,
            'description' => $this->description, 'management_permission' => $this->managementPermission,
            'required' => $this->required, 'secret' => $this->secret, 'frontend' => $this->frontend, 'default' => $this->default];
    }

    public static function validateCatalog(array $entries, string $appKey, array $permissions): void
    {
        if (! array_is_list($entries) || count($entries) > 1000) {
            throw new InvalidArgumentException('Invalid settings catalog.');
        }
        $seen = [];
        foreach ($entries as $entry) {
            if (! is_array($entry) || count($entry) !== 9
                || array_diff(array_keys($entry), ['key', 'scope', 'type', 'description', 'management_permission', 'required', 'secret', 'frontend', 'default']) !== []
                || ! is_string($entry['key'] ?? null) || ! is_string($entry['scope'] ?? null)
                || ! is_string($entry['type'] ?? null) || ! is_string($entry['description'] ?? null)
                || ! is_string($entry['management_permission'] ?? null)
                || ! is_bool($entry['required'] ?? null) || ! is_bool($entry['secret'] ?? null) || ! is_bool($entry['frontend'] ?? null)
                || (! is_scalar($entry['default']) && $entry['default'] !== null)) {
                throw new InvalidArgumentException('Settings accept definition fields only, never values.');
            }
            $setting = new self($entry['key'], $entry['scope'], $entry['type'], $entry['description'], $entry['management_permission'],
                $entry['required'], $entry['secret'], $entry['frontend'], $entry['default']);
            $identity = $setting->scope.':'.$setting->key;
            if (! str_starts_with($setting->key, $appKey.'.') || isset($seen[$identity])
                || ! str_starts_with($setting->managementPermission, $appKey.'.')
                || ! in_array($setting->managementPermission, $permissions, true)) {
                throw new InvalidArgumentException('Setting ownership, uniqueness or permission reference failed.');
            }
            $seen[$identity] = true;
        }
    }
}
