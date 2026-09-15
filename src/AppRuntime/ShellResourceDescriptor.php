<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use InvalidArgumentException;

/** App-owned overrides for the host's canonical resource route set. */
final readonly class ShellResourceDescriptor
{
    /**
     * @param list<string>|null $shapes
     * @param array<string, string> $overrides
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
    }

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        $shapes = $config['shapes'] ?? null;
        if ($shapes !== null && (! is_array($shapes) || array_filter($shapes, 'is_string') !== $shapes)) {
            throw new InvalidArgumentException('Shell resource shapes must be a list of strings.');
        }
        $overrides = $config['overrides'] ?? [];
        if (! is_array($overrides)) {
            throw new InvalidArgumentException('Shell resource overrides must be a map.');
        }
        foreach ($overrides as $tail => $component) {
            if (! is_string($tail) || ! is_string($component)) {
                throw new InvalidArgumentException('Shell resource overrides must map strings to strings.');
            }
        }

        return new self(
            path: is_string($config['path'] ?? null) ? $config['path'] : null,
            shapes: $shapes === null ? null : array_values($shapes),
            componentPrefix: is_string($config['componentPrefix'] ?? null) ? $config['componentPrefix'] : null,
            overrides: $overrides,
        );
    }
}
