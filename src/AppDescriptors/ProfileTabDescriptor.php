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
        public string $component,
        public string $permission,
        public int $sort = 100,
        public DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        if (strlen($key) > 191 || ! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $key)
            || strlen($labelKey) > 191 || trim($labelKey) !== $labelKey || $labelKey === ''
            || ! preg_match('/\A[A-Za-z][A-Za-z0-9_.-]{0,190}\z/D', $component)
            || ! preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]{0,180}\z/D', $permission)) {
            throw new InvalidArgumentException('Invalid profile tab declaration.');
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
