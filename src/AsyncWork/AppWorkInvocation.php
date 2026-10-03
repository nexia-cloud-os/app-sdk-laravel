<?php

declare(strict_types=1);

namespace Nexia\AsyncWork;

use InvalidArgumentException;

/** Immutable work payload that Core has already scoped to one App installation. */
final readonly class AppWorkInvocation
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $executionId,
        public string $key,
        public int $attempt,
        public array $payload,
    ) {
        if ($executionId === '' || strlen($executionId) > 128) {
            throw new InvalidArgumentException('App work execution identity is invalid.');
        }
        if (preg_match('/\A[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $key) !== 1) {
            throw new InvalidArgumentException('App work key is invalid.');
        }
        if ($attempt < 1 || $attempt > 100) {
            throw new InvalidArgumentException('App work attempt is invalid.');
        }
        try {
            if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 65536) {
                throw new InvalidArgumentException('App work payload exceeds the limit.');
            }
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('App work payload is invalid.', previous: $exception);
        }
    }
}
