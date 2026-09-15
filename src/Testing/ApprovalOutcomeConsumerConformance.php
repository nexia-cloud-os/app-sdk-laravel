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
use Nexia\ResourceReference\ResourceRef;
use Throwable;

/**
 * Reusable App test fixture for the runtime half of the Approval-outcome
 * contract — the half {@see ApprovalProcessConformance} cannot see.
 *
 * That fixture reads a BPMN structure and a descriptor: both are static, so it
 * proves the shape of the process but nothing about how the App's consumer
 * behaves when Core answers. The rules that decide whether a real instance
 * recovers or stalls live only in prose, and a consumer can violate every one
 * of them while passing a structural check.
 *
 * The rule this enforces was learned from a live stall. A consumer that treats
 * a `Rejected` delivery as its own failure and throws takes down more than its
 * own work: Core writes the immutable rejected evidence and opens the
 * ProcessIncident *inside the caller's transaction*, so the throw rolls both
 * away. The operator is left with a token parked forever and nothing naming
 * why, and a retry only reproduces the same mismatch. A rejection is a
 * processed negative outcome; the incident and inbound-message revalidation own
 * the repair.
 *
 * Usage — pass a closure that runs the App's consumer against the supplied
 * runtime, exactly as the Event Inbox would:
 *
 *   ApprovalOutcomeConsumerConformance::assert(
 *       fn (ProcessRuntime $runtime) => (new CorrelateLeaveProcess($runtime))
 *           ->approvalOutcome($envelope),
 *   );
 */
final class ApprovalOutcomeConsumerConformance
{
    private function __construct() {}

    /**
     * @param callable(ProcessRuntime): void $runConsumer
     *
     * @throws LogicException when the consumer violates the runtime contract
     */
    public static function assert(callable $runConsumer, ?ResourceRef $expectedProcessResourceRef = null): void
    {
        $violations = self::violations($runConsumer, $expectedProcessResourceRef);
        if ($violations !== []) {
            throw new LogicException(
                "Approval outcome consumer conformance failed:\n- ".implode("\n- ", $violations),
            );
        }
    }

    /**
     * @param callable(ProcessRuntime): void $runConsumer
     * @return list<string>
     */
    public static function violations(callable $runConsumer, ?ResourceRef $expectedProcessResourceRef = null): array
    {
        $violations = [];

        // A rejection must not propagate as an exception.
        $rejecting = self::runtime(new ProcessMessageDeliveryResult(
            ProcessMessageDeliveryStatus::Rejected,
            'process_message_correlation_mismatch',
        ));
        $thrown = self::capture($runConsumer, $rejecting);
        if ($thrown !== null) {
            $violations[] = sprintf(
                'The consumer threw %s on a Rejected delivery. Core already wrote the '
                .'immutable rejected evidence and opened an incident inside this '
                .'transaction, so throwing rolls both away and leaves the operator '
                .'nothing to repair. Record it and let the transaction commit.',
                $thrown::class,
            );
        }
        if (! $rejecting->delivered) {
            $violations[] = 'The consumer never called ProcessRuntime::deliverMessage().';
        } elseif ($expectedProcessResourceRef !== null
            && ! self::sameResourceIdentity($rejecting->delivery?->resourceRef, $expectedProcessResourceRef)) {
            $violations[] = 'The consumer delivered the Approval document subject as Process message scope. '
                .'Process messages must carry the originating Process instance resource reference; validate '
                .'the downstream Approval subject separately before delivery.';
        }

        // Duplicate delivery is normal replay, not an error.
        $replaying = self::runtime(new ProcessMessageDeliveryResult(
            ProcessMessageDeliveryStatus::AlreadyConsumed,
        ));
        $thrown = self::capture($runConsumer, $replaying);
        if ($thrown !== null) {
            $violations[] = sprintf(
                'The consumer threw %s on an AlreadyConsumed delivery. A duplicate is '
                .'normal replay and must be a no-op.',
                $thrown::class,
            );
        }

        // An outcome that arrives before the token is waiting parks in Core.
        $parking = self::runtime(new ProcessMessageDeliveryResult(
            ProcessMessageDeliveryStatus::Pending,
        ));
        $thrown = self::capture($runConsumer, $parking);
        if ($thrown !== null) {
            $violations[] = sprintf(
                'The consumer threw %s on a Pending delivery. An early outcome parks in '
                .'Core and is correlated when the token arrives.',
                $thrown::class,
            );
        }

        return $violations;
    }

    /** @param callable(ProcessRuntime): void $runConsumer */
    private static function capture(callable $runConsumer, ProcessRuntime $runtime): ?Throwable
    {
        try {
            $runConsumer($runtime);

            return null;
        } catch (Throwable $exception) {
            return $exception;
        }
    }

    private static function runtime(ProcessMessageDeliveryResult $result): ProcessRuntime
    {
        return new class($result) implements ProcessRuntime
        {
            public bool $delivered = false;

            public ?ProcessMessageDelivery $delivery = null;

            public function __construct(private readonly ProcessMessageDeliveryResult $result) {}

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
                $this->delivery = $delivery;

                return $this->result;
            }
        };
    }

    private static function sameResourceIdentity(?ResourceRef $actual, ResourceRef $expected): bool
    {
        return $actual instanceof ResourceRef
            && $actual->appKey === $expected->appKey
            && $actual->resourceKey === $expected->resourceKey
            && $actual->resourceId === $expected->resourceId;
    }
}
