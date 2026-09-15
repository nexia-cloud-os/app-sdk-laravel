<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventEnvelope;
use Nexia\Events\InboxReplayQuery;

/** Reads a tenant-local, already received envelope for an App-owned retry. */
interface InboxReplaySource
{
    public function find(InboxReplayQuery $query): ?EventEnvelope;
}
