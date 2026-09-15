<?php

declare(strict_types=1);

namespace Nexia\DataMigration;

/** Server-owned lifecycle for one concrete data-migration execution. */
enum DataMigrationRunStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
}
