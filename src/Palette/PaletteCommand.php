<?php

declare(strict_types=1);

namespace Nexia\Palette;

use ArrayAccess;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * Builds the stable array shape consumed by the host command palette.
 *
 * `labelKey`/`descriptionKey` are exact locale-catalog keys; code never
 * carries translated copy. The host materializes localized text at the
 * payload boundary.
 */
final readonly class PaletteCommand implements ArrayAccess, IteratorAggregate
{
    /**
     * @param array<string, mixed> $action
     * @param string|list<string>|null $permission
     * @param list<string>|null $shortcutKeys
     */
    public function __construct(
        public string $id,
        public string $labelKey,
        public array $action,
        public ?string $descriptionKey = null,
        public string|array|null $permission = null,
        public ?array $shortcutKeys = null,
        public ?string $icon = null,
    ) {}

    /**
     * @param  array<string, mixed>  $action
     * @param  string|list<string>|null  $permission
     * @param  list<string>|null  $shortcutKeys
     */
    public static function make(
        string $id,
        string $labelKey,
        array $action,
        ?string $descriptionKey = null,
        string|array|null $permission = null,
        ?array $shortcutKeys = null,
        ?string $icon = null,
    ): self {
        return new self(
            id: $id,
            labelKey: $labelKey,
            action: $action,
            descriptionKey: $descriptionKey,
            permission: $permission,
            shortcutKeys: $shortcutKeys,
            icon: $icon === null || $icon === '' ? null : $icon,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $entry = ['id' => $this->id, 'label_key' => $this->labelKey];
        if ($this->descriptionKey !== null) {
            $entry['description_key'] = $this->descriptionKey;
        }
        $entry['permission'] = $this->permission;
        $entry['action'] = $this->action;
        if ($this->shortcutKeys !== null) {
            $entry['shortcut_keys'] = $this->shortcutKeys;
        }
        if ($this->icon !== null) {
            $entry['icon'] = $this->icon;
        }

        return $entry;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->toArray()[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Palette commands are immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Palette commands are immutable.');
    }

    public function getIterator(): Traversable
    {
        yield from $this->toArray();
    }
}
