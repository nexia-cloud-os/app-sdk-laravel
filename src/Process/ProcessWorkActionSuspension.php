<?php

declare(strict_types=1);

namespace Nexia\Process;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Signature\SignatureProcessWaitDescriptor;

/** Host-neutral instruction to keep the current work-action token waiting. */
final readonly class ProcessWorkActionSuspension
{
    public const KIND_APPROVAL = 'approval';

    public const KIND_SIGNATURE = 'signature';

    public const KIND_OPERATION = 'operation';

    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $kind,
        public string $topic,
        public string $referenceId,
        public string $phase,
        public array $metadata = [],
    ) {
        if (! in_array($kind, [self::KIND_APPROVAL, self::KIND_SIGNATURE, self::KIND_OPERATION], true)) {
            throw new InvalidArgumentException("Unsupported Process work-action suspension kind [{$kind}].");
        }
        foreach (['topic' => $topic, 'referenceId' => $referenceId, 'phase' => $phase] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Process work-action suspension {$field} must be non-blank and normalized.");
            }
        }
    }

    /** @param array<string, mixed> $metadata */
    public static function waitingForApproval(string $topic, string $approvalCasePublicId, array $metadata = []): self
    {
        return new self(
            kind: self::KIND_APPROVAL,
            topic: $topic,
            referenceId: $approvalCasePublicId,
            phase: 'awaiting_approval',
            metadata: $metadata,
        );
    }

    public static function waitingForSignature(
        SignatureProcessWaitDescriptor $wait,
        ?string $timeoutAt = null,
        string $cancellationPolicy = 'preserve',
    ): self {
        if ($timeoutAt !== null) {
            if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $timeoutAt) !== 1) {
                throw new InvalidArgumentException('Signature Process suspension timeoutAt must be an ISO-8601 timestamp with an explicit timezone.');
            }
            try {
                new DateTimeImmutable($timeoutAt);
            } catch (\Exception) {
                throw new InvalidArgumentException('Signature Process suspension timeoutAt is invalid.');
            }
        }
        if (! in_array($cancellationPolicy, ['preserve', 'cancel_request'], true)) {
            throw new InvalidArgumentException('Signature Process suspension cancellation policy is unsupported.');
        }

        return new self(
            kind: self::KIND_SIGNATURE,
            topic: 'signature.request.outcome.v1',
            referenceId: $wait->requestPublicId,
            phase: 'awaiting_signature',
            metadata: [
                'wait' => $wait->toArray(),
                'timeout_at' => $timeoutAt,
                'cancellation_policy' => $cancellationPolicy,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'topic' => $this->topic,
            'reference_id' => $this->referenceId,
            'phase' => $this->phase,
            'metadata' => $this->metadata,
        ];
    }
}
