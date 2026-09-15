<?php

declare(strict_types=1);

namespace Nexia\Events;

use InvalidArgumentException;

/** A durable publication identifier returned only when an App needs to link its state to an event. */
final readonly class EventPublicationReceipt
{
    public function __construct(public string $publicationId)
    {
        if ($publicationId === '' || $publicationId !== trim($publicationId)) {
            throw new InvalidArgumentException('An event publication identifier must be non-blank and normalized.');
        }
    }
}
