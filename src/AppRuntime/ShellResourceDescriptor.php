<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use InvalidArgumentException;

/** App-owned overrides for the host's canonical resource route set. */
final readonly class ShellResourceDescriptor
{
    /**
     * @param list<string>|null $shapes
     * @param array<string, string|array{component: string, mode: 'list'|'show'|'create'|'edit', permissionAction?: string}> $overrides
     * String overrides are standalone pages. Structured overrides explicitly
     * retain the owning Resource identity; mode supplies the default permission
     * action, or permissionAction names an action declared by that Resource.
     */
    public function __construct(
        public ?string $path = null,
        public ?array $shapes = null,
        public ?string $componentPrefix = null,
        public array $overrides = [],
    ) {
        if ($this->path !== null && trim($this->path) === '') {
            throw new InvalidArgumentException('Shell resource path cannot be empty.');
        }
        if ($this->componentPrefix !== null && trim($this->componentPrefix) === '') {
            throw new InvalidArgumentException('Shell resource component prefix cannot be empty.');
        }
        if ($shapes !== null && (! array_is_list($shapes)
            || array_filter($shapes, static fn ($shape) => ! is_string($shape) || ! in_array($shape, ['list', 'show', 'form', 'record'], true)) !== []
            || count(array_unique($shapes)) !== count($shapes))) {
            throw new InvalidArgumentException('Shell resource shapes must be distinct supported names.');
        }
        if ($shapes !== null && in_array('record', $shapes, true)
            && array_intersect($shapes, ['show', 'form']) !== []) {
            throw new InvalidArgumentException('Use record or separate show/form shapes, not both.');
        }
        foreach ($overrides as $tail => $override) {
            if (! is_string($tail)) {
                throw new InvalidArgumentException('Shell resource override paths must be strings.');
            }
            $component = is_array($override) ? ($override['component'] ?? null) : $override;
            if (! is_string($component) || strlen($component) > 191
                || preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/D', $component) !== 1) {
                throw new InvalidArgumentException('Shell resource overrides require a component name.');
            }
            if (is_array($override)) {
                if (array_diff(array_keys($override), ['component', 'mode', 'permissionAction']) !== []
                    || ! in_array($override['mode'] ?? null, ['list', 'show', 'create', 'edit'], true)) {
                    throw new InvalidArgumentException('Shell resource page overrides require an explicit supported mode.');
                }
                if (array_key_exists('permissionAction', $override)
                    && (! is_string($override['permissionAction']) || strlen($override['permissionAction']) > 191
                        || preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $override['permissionAction']) !== 1)) {
                    throw new InvalidArgumentException('Shell resource page permission action is invalid.');
                }
            }
        }
    }

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        $shapes = $config['shapes'] ?? null;
        if ($shapes !== null && (! is_array($shapes) || ! array_is_list($shapes) || array_filter($shapes, 'is_string') !== $shapes)) {
            throw new InvalidArgumentException('Shell resource shapes must be a list of strings.');
        }
        $overrides = $config['overrides'] ?? [];
        if (! is_array($overrides)) {
            throw new InvalidArgumentException('Shell resource overrides must be a map.');
        }
        return new self(
            path: is_string($config['path'] ?? null) ? $config['path'] : null,
            shapes: $shapes === null ? null : array_values($shapes),
            componentPrefix: is_string($config['componentPrefix'] ?? null) ? $config['componentPrefix'] : null,
            overrides: $overrides,
        );
    }
}
