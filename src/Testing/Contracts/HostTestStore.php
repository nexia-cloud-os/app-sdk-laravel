<?php

declare(strict_types=1);

namespace Nexia\Testing\Contracts;

use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Testing\HostTestRecord;

/**
 * Test-only host fixture and evidence surface.
 *
 * Apps receive projections, never host Eloquent model classes.
 */
interface HostTestStore
{
    public function syncRolesToLegalEntity(LegalEntity $legalEntity): void;

    /** @param array<string, mixed> $attributes */
    public function createProcessDefinition(array $attributes): HostTestRecord;

    /** @param array<string, mixed> $attributes */
    public function createProcessInstance(array $attributes): HostTestRecord;

    /** @param array<string, mixed> $attributes */
    public function createProcessToken(array $attributes): HostTestRecord;

    public function processInstance(string $publicId): ?HostTestRecord;

    public function processToken(int|string $key): ?HostTestRecord;

    /** @param list<int|string> $keys */
    public function processTokenCount(array $keys, string $status): int;

    /** @param array<string, mixed> $attributes */
    public function createFile(array $attributes): HostTestRecord;

    public function inboxMessage(string $consumerKey): ?HostTestRecord;

    public function inboxMessageCount(string $consumerKey): int;

    public function outboxMessage(
        string $eventName,
        ?string $aggregateId = null,
        bool $latest = false,
    ): ?HostTestRecord;

    public function outboxMessageCount(string $eventName): int;
}
