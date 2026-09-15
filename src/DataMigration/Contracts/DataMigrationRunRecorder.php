<?php

declare(strict_types=1);

namespace Nexia\DataMigration\Contracts;

use Nexia\DataMigration\DataMigrationRunRecord;
use Nexia\DataMigration\DataMigrationRunResult;
use Nexia\DataMigration\DataMigrationRunStart;

/** Host capability for durable, server-owned data-migration execution evidence. */
interface DataMigrationRunRecorder
{
    public function start(DataMigrationRunStart $start): DataMigrationRunRecord;

    public function completed(
        string $publicId,
        ?DataMigrationRunResult $result = null,
    ): DataMigrationRunRecord;

    public function completedWithErrors(
        string $publicId,
        ?DataMigrationRunResult $result = null,
    ): DataMigrationRunRecord;

    public function failed(
        string $publicId,
        ?DataMigrationRunResult $result = null,
    ): DataMigrationRunRecord;
}
