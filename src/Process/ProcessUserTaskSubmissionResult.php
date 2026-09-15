<?php

declare(strict_types=1);

namespace Nexia\Process;

/** Sanitized App output that Core may retain and materialize after a UserTask write. */
final readonly class ProcessUserTaskSubmissionResult
{
    /** @param array<string, mixed> $output */
    public function __construct(public array $output = []) {}
}
