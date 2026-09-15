<?php

declare(strict_types=1);

namespace Nexia\Process;

use Nexia\Signature\SignatureProcessWaitDescriptor;

/** Successful App work-action output materialized through BPMN Activity IO. */
final readonly class ProcessWorkActionResult
{
    /** @param array<string, mixed> $output */
    public function __construct(
        public array $output = [],
        public ?ProcessWorkActionSuspension $suspension = null,
    ) {}

    /** @param array<string, mixed> $output */
    public static function waitingForApproval(
        array $output,
        string $outcomeTopic,
        string $approvalCasePublicId,
        array $metadata = [],
    ): self {
        return new self(
            output: $output,
            suspension: ProcessWorkActionSuspension::waitingForApproval(
                $outcomeTopic,
                $approvalCasePublicId,
                $metadata,
            ),
        );
    }

    /** @param array<string, mixed> $output */
    public static function waitingForSignature(
        array $output,
        SignatureProcessWaitDescriptor $wait,
        ?string $timeoutAt = null,
        string $cancellationPolicy = 'preserve',
    ): self {
        return new self(
            output: $output,
            suspension: ProcessWorkActionSuspension::waitingForSignature($wait, $timeoutAt, $cancellationPolicy),
        );
    }
}
