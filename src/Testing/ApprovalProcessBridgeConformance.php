<?php

declare(strict_types=1);

namespace Nexia\Testing;

use LogicException;
use Nexia\Process\Contracts\ProcessRuntime;
use Nexia\Process\Domain\Enums\ProcessMessageDeliveryStatus;
use Nexia\Process\ProcessInstanceSnapshot;
use Nexia\Process\ProcessMessageDelivery;
use Nexia\Process\ProcessMessageDeliveryResult;
use Nexia\Process\ReceiveTaskCorrelation;
use Throwable;

/**
 * Reusable App test fixture for the App-state half of a Process Approval bridge.
 *
 * Static BPMN conformance cannot prove that the App recorded its immutable
 * submission, or that the outcome consumer applied that submission before it
 * released the Process token. This fixture crosses that real wiring boundary.
 */
final class ApprovalProcessBridgeConformance
{
    private function __construct() {}

    /**
     * @param  callable(ProcessRuntime): void  $runOutcomeConsumer
     * @param  callable(): bool  $hasFrozenSubmission
     * @param  callable(): bool  $appStateIsApplied
     */
    public static function assert(
        callable $runOutcomeConsumer,
        callable $hasFrozenSubmission,
        callable $appStateIsApplied,
    ): void {
        $violations = self::violations($runOutcomeConsumer, $hasFrozenSubmission, $appStateIsApplied);
        if ($violations !== []) {
            throw new LogicException(
                "Approval Process bridge conformance failed:\n- ".implode("\n- ", $violations),
            );
        }
    }

    /**
     * @param  callable(ProcessRuntime): void  $runOutcomeConsumer
     * @param  callable(): bool  $hasFrozenSubmission
     * @param  callable(): bool  $appStateIsApplied
     * @return list<string>
     */
    public static function violations(
        callable $runOutcomeConsumer,
        callable $hasFrozenSubmission,
        callable $appStateIsApplied,
    ): array {
        $violations = [];
        if (! $hasFrozenSubmission()) {
            $violations[] = 'The Process submission did not create App-owned frozen Approval evidence.';
        }

        $runtime = new class($appStateIsApplied) implements ProcessRuntime
        {
            public bool $delivered = false;

            public bool $appStateWasAppliedBeforeDelivery = false;

            /** @param callable(): bool $appStateIsApplied */
            public function __construct(private readonly mixed $appStateIsApplied) {}

            public function hasPublishedEventStart(int $legalEntityKey, string $eventName): bool
            {
                return true;
            }

            public function findInstance(string $publicId): ?ProcessInstanceSnapshot
            {
                return null;
            }

            public function correlateReceiveTask(ReceiveTaskCorrelation $correlation): bool
            {
                return true;
            }

            public function deliverMessage(ProcessMessageDelivery $delivery): ProcessMessageDeliveryResult
            {
                $this->delivered = true;
                $this->appStateWasAppliedBeforeDelivery = ($this->appStateIsApplied)();

                return new ProcessMessageDeliveryResult(ProcessMessageDeliveryStatus::Consumed);
            }
        };

        try {
            $runOutcomeConsumer($runtime);
        } catch (Throwable $exception) {
            $violations[] = sprintf('The full outcome consumer threw %s.', $exception::class);
        }

        if (! $runtime->delivered) {
            $violations[] = 'The full outcome consumer never delivered the Approval outcome to Process.';
        } elseif (! $runtime->appStateWasAppliedBeforeDelivery) {
            $violations[] = 'The Process token advanced before the App applied its frozen Approval submission.';
        }

        return $violations;
    }
}
