<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\AppDescriptors\Contracts\AppDescriptor;

/** Binds an exact rendering purpose to existing App permissions; it grants no access by itself. */
final readonly class OfficialSealUseDescriptor implements AppDescriptor
{
    public string $key;

    /** @param list<string> $permissionKeys Any one listed permission must be authorized. */
    public function __construct(
        public string $appKey,
        public string $purpose,
        public array $permissionKeys,
        public DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        $this->key = $purpose;
        if (! preg_match('/\A[a-z][a-z0-9-]*\z/D', $appKey)
            || ! str_starts_with($purpose, $appKey.'.') || strlen($purpose) > 180
            || ! preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $purpose)
            || ! array_is_list($permissionKeys) || $permissionKeys === [] || count($permissionKeys) > 10
            || count(array_unique($permissionKeys, SORT_REGULAR)) !== count($permissionKeys)) {
            throw new InvalidArgumentException('Invalid official seal use declaration.');
        }
        foreach ($permissionKeys as $permission) {
            if (! is_string($permission) || ! str_starts_with($permission, $appKey.'.') || strlen($permission) > 191
                || ! preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $permission)) {
                throw new InvalidArgumentException('Official seal permissions must belong to the declaring App.');
            }
        }
    }

    public function descriptorKey(): string
    {
        return $this->purpose;
    }
}
