<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\AppDescriptors\Contracts\AppDescriptor;

/** An App-owned profile screen and its entry permission. */
final readonly class ProfileTabDescriptor implements AppDescriptor
{
    public function __construct(
        public string $key,
        public string $labelKey,
        public ?string $component = null,
        public ?string $permission = null,
        public int $sort = 100,
        public DescriptorStatus $status = DescriptorStatus::Active,
        public array $widgetKeys = [],
        public array $legacyTabIds = [],
    ) {
        if (strlen($key) > 191 || ! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $key)
            || strlen($labelKey) > 191 || trim($labelKey) !== $labelKey || $labelKey === ''
            || ($widgetKeys === [] && ($component === null || $permission === null
                || ! preg_match('/\A[A-Za-z][A-Za-z0-9_.-]{0,190}\z/D', $component)
                || ! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]{0,180}\z/D', $permission)))
            || ($widgetKeys !== [] && ($component !== null || $permission !== null))) {
            throw new InvalidArgumentException('Invalid profile tab declaration.');
        }
        foreach ([$widgetKeys, $legacyTabIds] as $values) {
            if (! array_is_list($values) || count($values) > 50
                || count(array_unique($values, SORT_REGULAR)) !== count($values)) {
                throw new InvalidArgumentException('Profile tab references must be unique lists.');
            }
        }
        $owner = strstr($key, '.', true).'.';
        foreach ($widgetKeys as $widgetKey) {
            if (! is_string($widgetKey) || strlen($widgetKey) > 191
                || ! str_starts_with($widgetKey, $owner)
                || ! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $widgetKey)) {
                throw new InvalidArgumentException('Profile tabs may only compose their own widgets.');
            }
        }
        foreach ($legacyTabIds as $id) {
            if (! is_string($id) || strlen($id) > 191 || $id === 'overview'
                || ! preg_match('/\A[a-z][a-z0-9_-]*\z/D', $id)) {
                throw new InvalidArgumentException('Invalid legacy profile tab address.');
            }
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
