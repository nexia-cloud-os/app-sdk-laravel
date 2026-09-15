<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;

/** PII-free Process variable contract for one terminal SignatureRequest fact. */
final readonly class SignatureProcessTerminalResult
{
    public function __construct(
        public string $requestPublicId,
        public SignatureRequestOutcome $outcome,
        public string $occurredAt,
    ) {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $requestPublicId) !== 1) {
            throw new InvalidArgumentException('Signature Process terminal result requestPublicId must be a UUID.');
        }
        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $occurredAt) !== 1) {
            throw new InvalidArgumentException('Signature Process terminal result occurredAt must be an ISO-8601 timestamp with an explicit timezone.');
        }
        try {
            new DateTimeImmutable($occurredAt);
        } catch (\Exception) {
            throw new InvalidArgumentException('Signature Process terminal result occurredAt is invalid.');
        }
    }

    /** @return array{signature_request_public_id:string,signature_terminal_outcome:string,signature_occurred_at:string} */
    public function toArray(): array
    {
        return [
            'signature_request_public_id' => $this->requestPublicId,
            'signature_terminal_outcome' => $this->outcome->value,
            'signature_occurred_at' => $this->occurredAt,
        ];
    }

    /** @param array{signature_request_public_id:string,signature_terminal_outcome:string,signature_occurred_at:string} $data */
    public static function fromArray(array $data): self
    {
        $requestPublicId = $data['signature_request_public_id'] ?? null;
        $terminalOutcome = $data['signature_terminal_outcome'] ?? null;
        $occurredAt = $data['signature_occurred_at'] ?? null;
        if (! is_string($requestPublicId) || ! is_string($terminalOutcome) || ! is_string($occurredAt)) {
            throw new InvalidArgumentException('Signature Process terminal result serialization is invalid.');
        }

        try {
            $outcome = SignatureRequestOutcome::from($terminalOutcome);
        } catch (\ValueError) {
            throw new InvalidArgumentException('Signature Process terminal result contains an invalid outcome.');
        }

        return new self(
            requestPublicId: $requestPublicId,
            outcome: $outcome,
            occurredAt: $occurredAt,
        );
    }
}
