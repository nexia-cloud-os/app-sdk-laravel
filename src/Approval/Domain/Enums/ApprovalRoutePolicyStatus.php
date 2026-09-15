<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Enums;

/** Lifecycle of one immutable approval route-policy version. */
enum ApprovalRoutePolicyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Retired = 'retired';

    public function isMutable(): bool
    {
        return $this === self::Draft;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
