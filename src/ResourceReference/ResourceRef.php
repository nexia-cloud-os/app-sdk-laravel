<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use InvalidArgumentException;

/** Canonical public reference to a resource owned by an App. */
final readonly class ResourceRef
{
    private const APP_KEY_PATTERN = '/\A[a-z][a-z0-9]*(?:[-_][a-z0-9]+)*\z/D';

    private const RESOURCE_SEGMENT_PATTERN = '/\A[a-z][a-z0-9_-]*\z/D';

    public function __construct(
        public string $appKey,
        public string $resourceKey,
        public string $resourceId,
        public string $display,
        public ?string $href = null,
    ) {
        if (! self::hasCanonicalReference($appKey, $resourceKey, $resourceId)) {
            throw new InvalidArgumentException(
                'ResourceRef must carry a canonical app key, fully qualified resource key, and non-blank resource id.',
            );
        }
    }

    public static function hasCanonicalIdentity(string $appKey, string $resourceKey): bool
    {
        if (preg_match(self::APP_KEY_PATTERN, $appKey) !== 1
            || mb_strlen($resourceKey) > 160
            || $resourceKey !== trim($resourceKey)) {
            return false;
        }

        $segments = explode('.', $resourceKey);

        if (count($segments) < 2 || array_shift($segments) !== $appKey) {
            return false;
        }

        return array_all(
            $segments,
            static fn (string $segment): bool => preg_match(self::RESOURCE_SEGMENT_PATTERN, $segment) === 1,
        );
    }

    public static function hasCanonicalReference(string $appKey, string $resourceKey, string $resourceId): bool
    {
        return self::hasCanonicalIdentity($appKey, $resourceKey)
            && $resourceId !== ''
            && mb_strlen($resourceId) <= 255
            && $resourceId === trim($resourceId);
    }

    /**
     * @return array{app_key: string, resource_key: string, resource_id: string, display: string, href?: string}
     */
    public function toArray(): array
    {
        $data = [
            'app_key' => $this->appKey,
            'resource_key' => $this->resourceKey,
            'resource_id' => $this->resourceId,
            'display' => $this->display,
        ];

        if ($this->href !== null && $this->href !== '') {
            $data['href'] = $this->href;
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            appKey: (string) ($data['app_key'] ?? ''),
            resourceKey: (string) ($data['resource_key'] ?? ''),
            resourceId: (string) ($data['resource_id'] ?? ''),
            display: (string) ($data['display'] ?? ''),
            href: isset($data['href']) ? (string) $data['href'] : null,
        );
    }
}
