<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;

/** Log-safe participant progress returned to an authorized adopting App. */
final readonly class SignatureParticipantProgress
{
    public SignatureParticipantRoutingState $routingState;

    public function __construct(
        public string $roleKey,
        public int $sequence,
        public SignatureParticipantStatus $status,
        public ?string $completedAt = null,
        public int $roleSlot = 1,
        ?SignatureParticipantRoutingState $routingState = null,
        public int $presentedRevision = 0,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1 || $sequence < 1 || $roleSlot < 1) {
            throw new InvalidArgumentException('Signature participant progress role, slot, and sequence are invalid.');
        }

        if ($presentedRevision < 0) {
            throw new InvalidArgumentException('Signature participant progress presented revision must be a non-negative ordinal.');
        }

        if ($completedAt !== null && ! self::isIsoTimestamp($completedAt)) {
            throw new InvalidArgumentException('Signature participant progress completion time must be an ISO-8601 timestamp with an explicit timezone.');
        }

        $this->routingState = $routingState ?? self::legacyRoutingState($status);
    }

    /** @return array{role_key: string, role_slot: int, sequence: int, status: string, routing_state: string, presented_revision: int, completed_at: string|null} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'role_slot' => $this->roleSlot,
            'sequence' => $this->sequence,
            'status' => $this->status->value,
            'routing_state' => $this->routingState->value,
            'presented_revision' => $this->presentedRevision,
            'completed_at' => $this->completedAt,
        ];
    }

    /** @param array{role_key: string, role_slot?: int, sequence: int, status: string, routing_state?: string, presented_revision?: int, completed_at?: string|null} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            roleKey: $data['role_key'],
            sequence: $data['sequence'],
            status: SignatureParticipantStatus::from($data['status']),
            completedAt: $data['completed_at'] ?? null,
            roleSlot: $data['role_slot'] ?? 1,
            routingState: isset($data['routing_state'])
                ? SignatureParticipantRoutingState::from($data['routing_state'])
                : null,
            presentedRevision: $data['presented_revision'] ?? 0,
        );
    }

    private static function legacyRoutingState(SignatureParticipantStatus $status): SignatureParticipantRoutingState
    {
        return match ($status) {
            SignatureParticipantStatus::Signed,
            SignatureParticipantStatus::Completed => SignatureParticipantRoutingState::Completed,
            SignatureParticipantStatus::Declined,
            SignatureParticipantStatus::Expired,
            SignatureParticipantStatus::Cancelled => SignatureParticipantRoutingState::Closed,
            SignatureParticipantStatus::Failed => SignatureParticipantRoutingState::Failed,
            default => SignatureParticipantRoutingState::Active,
        };
    }

    private static function isIsoTimestamp(string $value): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $value) !== 1) {
            return false;
        }

        try {
            new DateTimeImmutable($value);
        } catch (\Exception) {
            return false;
        }

        return true;
    }
}
