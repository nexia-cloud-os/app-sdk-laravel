<?php

declare(strict_types=1);

namespace Nexia\Notification;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/** One App-result notification fan-out request, bound to a consumed event. */
final readonly class NotificationPublication
{
    /** @param list<int> $recipientIds @param array<string, mixed> $params */
    public function __construct(
        public string $intentKey,
        public ResourceRef $owner,
        public int $legalEntityId,
        public array $recipientIds,
        public array $params,
        public string $dedupeKey,
    ) {
        if (! preg_match('/\A[a-z][a-z0-9._-]*\z/D', $intentKey)
            || $legalEntityId < 1
            || trim($dedupeKey) === ''
            || mb_strlen($dedupeKey) > 160
            || array_filter($recipientIds, static fn (mixed $id): bool => ! is_int($id) || $id < 1) !== []) {
            throw new InvalidArgumentException('Notification publication is invalid.');
        }
    }
}
