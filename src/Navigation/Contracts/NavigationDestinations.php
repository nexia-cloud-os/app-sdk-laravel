<?php

declare(strict_types=1);

namespace Nexia\Navigation\Contracts;

interface NavigationDestinations
{
    /** @param class-string $contributor */
    public function route(string $contributor): string;

    /** @param class-string $contributor */
    public function icon(string $contributor): string;

    /** @param class-string $contributor */
    public function permissionKey(string $contributor): ?string;

    /** @param class-string $contributor @return list<array<string, mixed>> */
    public function items(string $contributor): array;
}
