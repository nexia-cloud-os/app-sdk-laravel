<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\ResourceImport\ImportFileCapability;

/**
 * Declares a custom App import that consumes a Resource Import upload without
 * using the host's standard model transfer, pipeline, or recipe executor.
 *
 * Core still owns the upload, actor, tenant, legal-entity, malware, format,
 * and one-use consumption-key boundaries. A contribution must declare the
 * existing permission keys that authorize intake and its accepted formats.
 */
interface ImportFileCapabilityContribution
{
    /** @return list<ImportFileCapability> */
    public static function importFileCapabilities(): array;
}
