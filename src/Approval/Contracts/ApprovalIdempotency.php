<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Closure;
use Nexia\Approval\ApprovalIdempotencyKey;
use Nexia\Approval\ApprovalIdempotencyReplay;

interface ApprovalIdempotency
{
    /**
     * Execute once for this exact request identity, returning either the prior
     * durable result or the result produced by the callback.
     *
     * @param Closure(): ApprovalIdempotencyReplay $callback
     */
    public function execute(ApprovalIdempotencyKey $key, Closure $callback): ApprovalIdempotencyReplay;
}
