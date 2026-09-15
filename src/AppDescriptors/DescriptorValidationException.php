<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/**
 * A public App descriptor does not satisfy the SDK contract.
 *
 * Core may isolate this exception at an App contribution boundary. Other
 * exceptions remain fatal so authorization, tenant, database, and programming
 * failures are never mistaken for a malformed optional descriptor.
 */
final class DescriptorValidationException extends \InvalidArgumentException
{
    public function __construct(
        public readonly string $descriptorType,
        public readonly string $descriptorKey,
        public readonly ?string $path,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function lifecycleEvent(string $eventKey, string $message, ?string $path = null): self
    {
        $location = $path === null ? '' : " payload field [{$path}]";

        return new self(
            descriptorType: 'resource_lifecycle_event',
            descriptorKey: $eventKey,
            path: $path,
            message: "Public lifecycle event [{$eventKey}]{$location} {$message}",
        );
    }

    public static function eventPayload(string $eventKey, string $message, ?string $path = null): self
    {
        $location = $path === null ? '' : " payload field [{$path}]";

        return new self(
            descriptorType: 'event_payload',
            descriptorKey: $eventKey,
            path: $path,
            message: "Event [{$eventKey}]{$location} {$message}",
        );
    }

    public static function contribution(string $contributor, string $message, ?string $path = null): self
    {
        return new self(
            descriptorType: 'resource_contribution',
            descriptorKey: $contributor,
            path: $path,
            message: "Resource contribution [{$contributor}] {$message}",
        );
    }
}
