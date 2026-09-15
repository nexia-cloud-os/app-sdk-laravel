<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Workbook\Contracts;

/** Host guard for an untrusted workbook before an App parser opens its archive. */
interface SafeWorkbookInspector
{
    public function assertSafe(string $path, string $declaredMimeType): void;
}
