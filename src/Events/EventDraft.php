<?php

declare(strict_types=1);

namespace Nexia\Events;

use InvalidArgumentException;

/**
 * App-owned event intent. Tenant identity, producer identity, occurrence time,
 * and transport identity are injected only by the Core EventPublisher.
 */
final readonly class EventDraft
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $eventName,
        public array $payload,
        public ?LegalEntityScope $legalEntityScope = null,
        public ?ActorReference $actor = null,
        public ?string $aggregateType = null,
        public ?string $aggregateId = null,
        public ?string $correlationId = null,
        public ?string $causationId = null,
        public int $schemaVersion = 1,
    ) {
        foreach (['eventName' => $eventName] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Event draft {$field} must be non-blank and normalized.");
            }
        }

        foreach ([
            'aggregateType' => $aggregateType,
            'aggregateId' => $aggregateId,
            'correlationId' => $correlationId,
            'causationId' => $causationId,
        ] as $field => $value) {
            if ($value !== null && ($value === '' || $value !== trim($value))) {
                throw new InvalidArgumentException("Event draft {$field} must be null or a normalized non-blank string.");
            }
        }

        if ($schemaVersion < 1) {
            throw new InvalidArgumentException('Event draft schemaVersion must be positive.');
        }
    }
}
