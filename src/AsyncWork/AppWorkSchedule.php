<?php

declare(strict_types=1);

namespace Nexia\AsyncWork;

use InvalidArgumentException;

/** A Core-owned cron trigger for one declared App work key. */
final readonly class AppWorkSchedule
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $key,
        public string $workKey,
        public string $cron,
        public array $payload = [],
    ) {
        foreach ([$key, $workKey] as $value) {
            if (preg_match('/\A[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $value) !== 1) {
                throw new InvalidArgumentException('App work schedule key is invalid.');
            }
        }
        if (strlen($cron) > 100 || preg_match('/\A[0-9*\/,-]+(?:\s+[0-9*\/,-]+){4}\z/D', $cron) !== 1) {
            throw new InvalidArgumentException('App work schedule cron expression is invalid.');
        }
        try {
            if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 16384) {
                throw new InvalidArgumentException('App work schedule payload exceeds the limit.');
            }
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('App work schedule payload is invalid.', previous: $exception);
        }
    }

    /** @return array{key: string, work_key: string, cron: string, payload: array<string, mixed>} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'work_key' => $this->workKey, 'cron' => $this->cron, 'payload' => $this->payload];
    }
}
