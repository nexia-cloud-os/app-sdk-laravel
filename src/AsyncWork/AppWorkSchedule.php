<?php

declare(strict_types=1);

namespace Nexia\AsyncWork;

use InvalidArgumentException;
use Nexia\Events\EventDraft;
use Nexia\Events\LegalEntityScope;

/** A Core-owned cron trigger for one declared App work key. */
final readonly class AppWorkSchedule
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $key,
        public string $workKey,
        public string $cron,
        public array $payload = [],
        public bool $tenantManaged = false,
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

    /** Publish inside the same App transaction as the state change. Revisions increase per schedule. */
    public static function activationChanged(string $scheduleKey, bool $enabled, int $revision): EventDraft
    {
        if (! preg_match('/\A([a-z][a-z0-9-]*)\.[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)*\z/D', $scheduleKey, $owner)
            || strlen($scheduleKey) > 191 || $revision < 0) {
            throw new InvalidArgumentException('Invalid App schedule activation.');
        }

        return new EventDraft($owner[1].'.work_schedule.activation_changed',
            ['schedule_key' => $scheduleKey, 'enabled' => $enabled, 'revision' => $revision], LegalEntityScope::tenantWide());
    }

    /** @return array{key: string, work_key: string, cron: string, payload: array<string, mixed>, tenant_managed?: true} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'work_key' => $this->workKey, 'cron' => $this->cron, 'payload' => $this->payload,
            ...($this->tenantManaged ? ['tenant_managed' => true] : [])];
    }
}
