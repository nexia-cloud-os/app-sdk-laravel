<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Typed provider result that never exposes an App exception message. */
final readonly class SignatureBulkBindingPreviewResult
{
    private function __construct(
        public SignatureBulkBindingPreviewStatus $status,
        public ?SignatureBulkBindingExecutionPlan $plan,
        public ?SignatureBulkBindingFailureReason $reason,
        public ?SignatureBulkBindingParticipantFailure $participantFailure,
    ) {
        if (($status === SignatureBulkBindingPreviewStatus::Resolved) !== ($plan !== null)
            || ($status === SignatureBulkBindingPreviewStatus::Resolved) !== ($reason === null)
            || ($status !== SignatureBulkBindingPreviewStatus::Rejected && $participantFailure !== null)) {
            throw new InvalidArgumentException('Signature bulk preview result status, plan, and reason are inconsistent.');
        }
    }

    public static function resolved(SignatureBulkBindingExecutionPlan $plan): self
    {
        return new self(SignatureBulkBindingPreviewStatus::Resolved, $plan, null, null);
    }

    public static function rejected(
        SignatureBulkBindingFailureReason $reason,
        ?SignatureBulkBindingParticipantFailure $participantFailure = null,
    ): self {
        return new self(SignatureBulkBindingPreviewStatus::Rejected, null, $reason, $participantFailure);
    }

    public static function unavailable(SignatureBulkBindingFailureReason $reason): self
    {
        return new self(SignatureBulkBindingPreviewStatus::Unavailable, null, $reason, null);
    }

    /** Redacted public result. @return array{status:string,reason_code:string|null,participant_failure:array<string,mixed>|null,plan:array<string,mixed>|null} */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'reason_code' => $this->reason?->value,
            'participant_failure' => $this->participantFailure?->toArray(),
            'plan' => $this->plan?->toArray(),
        ];
    }

    /** @param array<string,mixed> $data @param array<string,mixed>|null $protectedPlanPayload */
    public static function fromArray(array $data, ?array $protectedPlanPayload = null): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'status',
            'reason_code',
            'participant_failure',
            'plan',
        ]);
        $status = SignatureBulkBindingPreviewStatus::from(
            SignatureBulkTemplateParticipantAssignment::string($data, 'status'),
        );
        $reason = SignatureBulkTemplateParticipantAssignment::nullableString($data, 'reason_code');
        $plan = $data['plan'];
        if ($plan !== null && ! is_array($plan)) {
            throw new InvalidArgumentException('Serialized signature bulk preview plan must be null or an object.');
        }
        if ($plan !== null && $protectedPlanPayload === null) {
            throw new InvalidArgumentException('Rehydrating a resolved signature bulk preview requires its protected plan payload.');
        }
        if ($plan === null && $protectedPlanPayload !== null) {
            throw new InvalidArgumentException('A non-resolved signature bulk preview cannot carry a protected plan payload.');
        }
        $participantFailure = $data['participant_failure'];
        if ($participantFailure !== null && ! is_array($participantFailure)) {
            throw new InvalidArgumentException('Serialized signature bulk participant failure must be null or an object.');
        }

        return new self(
            status: $status,
            plan: $plan === null ? null : SignatureBulkBindingExecutionPlan::fromArray($plan, $protectedPlanPayload),
            reason: $reason === null ? null : SignatureBulkBindingFailureReason::from($reason),
            participantFailure: $participantFailure === null
                ? null
                : SignatureBulkBindingParticipantFailure::fromArray($participantFailure),
        );
    }
}
