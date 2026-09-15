<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Reauthorization never returns a replacement plan, preserving approved content. */
final readonly class SignatureBulkBindingReauthorizationResult
{
    private function __construct(
        public SignatureBulkBindingReauthorizationStatus $status,
        public ?SignatureBulkBindingFailureReason $reason,
    ) {
        if (($status === SignatureBulkBindingReauthorizationStatus::Authorized) !== ($reason === null)) {
            throw new InvalidArgumentException('Signature bulk reauthorization status and reason are inconsistent.');
        }
    }

    public static function authorized(): self
    {
        return new self(SignatureBulkBindingReauthorizationStatus::Authorized, null);
    }

    public static function denied(SignatureBulkBindingFailureReason $reason): self
    {
        return new self(SignatureBulkBindingReauthorizationStatus::Denied, $reason);
    }

    public static function unavailable(SignatureBulkBindingFailureReason $reason): self
    {
        return new self(SignatureBulkBindingReauthorizationStatus::Unavailable, $reason);
    }

    /** @return array{status:string,reason_code:string|null} */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'reason_code' => $this->reason?->value,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, ['status', 'reason_code']);
        $reason = SignatureBulkTemplateParticipantAssignment::nullableString($data, 'reason_code');

        return new self(
            status: SignatureBulkBindingReauthorizationStatus::from(
                SignatureBulkTemplateParticipantAssignment::string($data, 'status'),
            ),
            reason: $reason === null ? null : SignatureBulkBindingFailureReason::from($reason),
        );
    }
}
