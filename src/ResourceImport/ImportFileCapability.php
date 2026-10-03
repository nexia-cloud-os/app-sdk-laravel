<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use InvalidArgumentException;

/**
 * A reviewed App import that may consume a completed Resource Import upload.
 *
 * The declaration identifies the exact target resource, the existing
 * permissions that authorize intake, and the file formats its handler accepts.
 * Core enforces the actor, tenant, legal-entity, expiry, malware, and one-use
 * upload boundaries; this value never transports a file or a handler class.
 */
final readonly class ImportFileCapability
{
    /**
     * @param list<string> $permissionKeys
     * @param list<string> $formats
     */
    public function __construct(
        public string $resourceKey,
        public array $permissionKeys,
        public array $formats,
    ) {
        if (preg_match('/\A[a-z0-9][a-z0-9._-]{0,119}\z/', $resourceKey) !== 1
            || ! self::stringList($permissionKeys)
            || ! self::stringList($formats)) {
            throw new InvalidArgumentException('Invalid import-file capability.');
        }
    }

    /** @return array{resource_key:string,permission_keys:list<string>,formats:list<string>} */
    public function toArray(): array
    {
        return [
            'resource_key' => $this->resourceKey,
            'permission_keys' => $this->permissionKeys,
            'formats' => $this->formats,
        ];
    }

    /** @param array<mixed> $values */
    private static function stringList(array $values): bool
    {
        if (! array_is_list($values) || $values === [] || count($values) > 1000
            || count(array_unique($values, SORT_REGULAR)) !== count($values)) {
            return false;
        }

        foreach ($values as $value) {
            if (! is_string($value) || preg_match('/\A[a-z][a-z0-9_.-]*\z/', $value) !== 1) {
                return false;
            }
        }

        return true;
    }
}
