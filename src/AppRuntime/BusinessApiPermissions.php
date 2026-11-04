<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use Attribute;
use InvalidArgumentException;

/** Declares additional permissions checked by a business API handler. */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class BusinessApiPermissions
{
    /** @param list<string> $permissions */
    public function __construct(public array $permissions)
    {
        if (! array_is_list($permissions) || count($permissions) > 100 || count($permissions) !== count(array_unique($permissions, SORT_REGULAR))) {
            throw new InvalidArgumentException('Business API permissions must be a unique list.');
        }
        foreach ($permissions as $permission) {
            if (! is_string($permission) || strlen($permission) > 160 || ! preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $permission)) {
                throw new InvalidArgumentException('Invalid business API permission.');
            }
        }
    }
}
