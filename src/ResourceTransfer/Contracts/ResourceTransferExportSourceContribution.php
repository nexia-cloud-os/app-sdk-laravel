<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer\Contracts;

use Nexia\ResourceTransfer\ResourceTransferExportSourceDefinition;

/** App/host contribution for an export-only composed or projected dataset. */
interface ResourceTransferExportSourceContribution
{
    /** @return list<ResourceTransferExportSourceDefinition> */
    public static function resourceTransferExportSources(): array;
}
