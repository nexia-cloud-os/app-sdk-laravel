<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;

/** Standalone public integration Event contributed through the App descriptor set. */
final readonly class EventDescriptor implements AppDescriptor
{
    /** @param array<string, mixed> $payloadSchema */
    public function __construct(
        public string $key,
        public int $schemaVersion,
        public string $aggregateType,
        public array $payloadSchema,
        public DescriptorStatus $status = DescriptorStatus::Active,
        public bool $publicForComposition = false,
        public ?string $labelKey = null,
        public ?string $replacementEventName = null,
    ) {
        self::assertEventName($this->key, 'key');

        if ($this->schemaVersion < 1) {
            throw new \InvalidArgumentException("Event descriptor [{$this->key}] schemaVersion must be positive.");
        }

        if (mb_strlen($this->aggregateType) > 160
            || preg_match('/\A[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $this->aggregateType) !== 1) {
            throw new \InvalidArgumentException("Event descriptor [{$this->key}] requires a normalized aggregateType.");
        }

        if ($this->labelKey !== null && ($this->labelKey === '' || trim($this->labelKey) !== $this->labelKey)) {
            throw new \InvalidArgumentException(
                "Event descriptor [{$this->key}] labelKey must be null or a normalized non-empty string.",
            );
        }

        if ($this->publicForComposition) {
            if ($this->labelKey === null) {
                throw new \InvalidArgumentException(
                    "Composable Event descriptor [{$this->key}] requires a non-empty labelKey.",
                );
            }

            PublicEventPayloadSchema::assertValid($this->key, $this->payloadSchema);
        } else {
            EventPayloadSchema::assertValid($this->key, $this->payloadSchema);
            PublicEventPayloadSchema::assertHandoffResultStates($this->key, $this->payloadSchema);
        }

        if ($this->replacementEventName !== null) {
            self::assertEventName($this->replacementEventName, 'replacementEventName');

            if ($this->replacementEventName === $this->key) {
                throw new \InvalidArgumentException(
                    "Event descriptor [{$this->key}] replacementEventName must not reference itself.",
                );
            }
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }

    private static function assertEventName(string $eventName, string $field): void
    {
        if (mb_strlen($eventName) > 160
            || preg_match('/\A[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $eventName) !== 1) {
            throw new \InvalidArgumentException(
                "Event descriptor {$field} must be a canonical full Event name.",
            );
        }
    }
}
