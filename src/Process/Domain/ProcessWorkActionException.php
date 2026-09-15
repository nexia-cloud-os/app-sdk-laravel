<?php

declare(strict_types=1);

namespace Nexia\Process\Domain;

use RuntimeException;

/** Stable App work-action failure that Core may retry or route to a boundary error. */
final class ProcessWorkActionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }
}
