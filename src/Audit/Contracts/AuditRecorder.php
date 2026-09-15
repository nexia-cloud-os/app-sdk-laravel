<?php

declare(strict_types=1);

namespace Nexia\Audit\Contracts;

use Nexia\Audit\AuditEntry;

/** Append-only Core audit boundary. */
interface AuditRecorder
{
    public function record(AuditEntry $entry): void;
}
