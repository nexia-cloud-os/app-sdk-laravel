<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Enums;

/** Whether a business approval also offers the Core rich-text editor. */
enum ApprovalDocumentEditorMode: string
{
    case None = 'none';
    case Optional = 'optional';
    case Required = 'required';

    public function isOffered(): bool
    {
        return $this !== self::None;
    }

    public function isRequired(): bool
    {
        return $this === self::Required;
    }
}
