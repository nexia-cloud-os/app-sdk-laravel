<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceTransfer\Contracts;

use Nexia\Laravel\ResourceTransfer\ResourceTransferExportRequest;

/** Supplies visibility-filtered rows for a contributed export dataset. */
interface ResourceTransferExportSource
{
    /** @return iterable<array<string, mixed>> */
    public function rows(ResourceTransferExportRequest $request): iterable;

    /** @return array<string, mixed> Durable, non-secret evidence retained with the export run. */
    public function evidence(ResourceTransferExportRequest $request): array;
}
