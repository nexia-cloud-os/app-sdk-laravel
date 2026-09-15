<?php

declare(strict_types=1);

namespace Nexia\Events;

/**
 * @deprecated Construct an EventDraft and publish it through EventPublisher.
 *             The wire envelope remains supported for transport consumers.
 */
final class EventEnvelope
{
    public function __construct(
        public readonly string $eventName,
        public readonly string $tenantId,
        public readonly array $payload,
        public readonly string $producerAppKey,
        public readonly int $schemaVersion = 1,
        public readonly ?int $legalEntityId = null,
        public readonly ?string $actorId = null,
        public readonly string $actorType = 'user',
        public readonly ?string $aggregateType = null,
        public readonly ?string $aggregateId = null,
        public readonly ?string $correlationId = null,
        public readonly ?string $causationId = null,
        public readonly ?string $occurredAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'tenant_id' => $this->tenantId,
            'schema_version' => $this->schemaVersion,
            'producer_app_key' => $this->producerAppKey,
            'legal_entity_id' => $this->legalEntityId,
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId,
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventName: $data['event_name'],
            tenantId: $data['tenant_id'],
            payload: $data['payload'] ?? [],
            producerAppKey: $data['producer_app_key'] ?? 'unknown',
            schemaVersion: $data['schema_version'] ?? 1,
            legalEntityId: $data['legal_entity_id'] ?? null,
            actorId: $data['actor_id'] ?? null,
            actorType: $data['actor_type'] ?? 'user',
            aggregateType: $data['aggregate_type'] ?? null,
            aggregateId: $data['aggregate_id'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            causationId: $data['causation_id'] ?? null,
            occurredAt: $data['occurred_at'] ?? null,
        );
    }

    public function toHeaders(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'producer_app_key' => $this->producerAppKey,
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'legal_entity_id' => $this->legalEntityId,
            'occurred_at' => $this->occurredAt,
        ];
    }

    public function identity(): string
    {
        return sha1(json_encode($this->identityPayload(), JSON_THROW_ON_ERROR));
    }

    /**
     * Deterministic UUID-shaped identity for causation chains.
     *
     * causation_id columns are uuid-typed across the platform, and the raw
     * hex from identity() does not fit them. The same envelope always yields
     * the same value, so consumers keep their idempotent causation semantics.
     */
    public function identityUuid(): string
    {
        $hex = substr($this->identity(), 0, 32);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    public function boundedIdentity(int $maxLength = 36): string
    {
        $identity = $this->identity();

        return strlen($identity) <= $maxLength
            ? $identity
            : substr(sha1($identity), 0, $maxLength);
    }

    /** @return array<string, mixed> */
    private function identityPayload(): array
    {
        return [
            'event_name' => $this->eventName,
            'tenant_id' => $this->tenantId,
            'legal_entity_id' => $this->legalEntityId,
            'producer_app_key' => $this->producerAppKey,
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId,
            'schema_version' => $this->schemaVersion,
            'correlation_id' => $this->correlationId,
            'causation_id' => is_string($this->causationId) ? trim($this->causationId) : null,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
        ];
    }
}
