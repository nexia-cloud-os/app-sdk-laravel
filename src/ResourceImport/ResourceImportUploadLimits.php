<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

/** Host/App contract for files admitted through the resource-import transport. */
final class ResourceImportUploadLimits
{
    public const MAX_UPLOAD_BYTES = 50 * 1024 * 1024;

    private function __construct() {}
}
