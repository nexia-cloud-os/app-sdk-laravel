<?php

declare(strict_types=1);

namespace Nexia\Notification\Contracts;

/** Stable, code-owned meaning and display policy for a durable notification. */
interface NotificationIntent
{
    public function key(): string;

    public function category(): string;

    /** @return array<string, mixed> */
    public function policySnapshot(): array;

    /** @param array<string, mixed> $params */
    public function deepLink(array $params): ?string;

    /** @param array<string, mixed> $params @return array{title: string, body: string|null} */
    public function render(array $params, string $locale): array;
}
