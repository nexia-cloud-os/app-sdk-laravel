<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Enums;

use Nexia\AppDescriptors\ApprovalFormBindingDescriptor;

/**
 * Which authoring path an approval form-template version drives.
 *
 * A free form is a Core-authored sign-off document. A business form references
 * an App {@see ApprovalFormBindingDescriptor} and may optionally include a
 * Core editor body according to {@see ApprovalDocumentEditorMode}.
 */
enum ApprovalFormKind: string
{
    case Free = 'free';
    case Business = 'business';

    public function isBusiness(): bool
    {
        return $this === self::Business;
    }

    public function isFree(): bool
    {
        return $this === self::Free;
    }
}
