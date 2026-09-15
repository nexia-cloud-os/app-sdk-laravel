<?php

declare(strict_types=1);

namespace Nexia\Process\Domain;

use RuntimeException;

/** Stable failure contract for an App-requested bound Process start. */
final class ProcessStartException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
    ) {
        parent::__construct($message);
    }
}
