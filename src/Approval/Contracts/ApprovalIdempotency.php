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
     * Optional preparation runs after the replay check, under the same request
     * lock but outside the host's write transaction. Its result is passed to the
     * callback; callback writes and the replay record commit atomically.
     * Preparation must not persist business state and is skipped on replay.
     *
     * @template TPrepared
     * @param (Closure(): ApprovalIdempotencyReplay)|(Closure(TPrepared): ApprovalIdempotencyReplay) $callback
     * @param null|(Closure(): TPrepared) $prepare
     */
    public function execute(ApprovalIdempotencyKey $key, Closure $callback, ?Closure $prepare = null): ApprovalIdempotencyReplay;
}
