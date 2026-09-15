<?php

declare(strict_types=1);

namespace Nexia\Events;

/**
 * Canonical terminal outcomes for work handed across App boundaries.
 *
 * RetryableFailed means the current attempt ended, but the same business
 * request may be attempted again. TerminalFailed means retrying that handoff
 * request is not valid. App-owned record lifecycle states are outside this
 * contract.
 */
enum HandoffResultState: string
{
    case Accepted = 'ACCEPTED';
    case Rejected = 'REJECTED';
    case Unauthorized = 'UNAUTHORIZED';
    case Stale = 'STALE';
    case RetryableFailed = 'RETRYABLE_FAILED';
    case TerminalFailed = 'TERMINAL_FAILED';

    /** @return non-empty-list<string> */
    public static function values(self $first, self ...$remaining): array
    {
        return array_map(
            static fn (self $state): string => $state->value,
            [$first, ...$remaining],
        );
    }

    public function isRetryableFailure(): bool
    {
        return $this === self::RetryableFailed;
    }
}
