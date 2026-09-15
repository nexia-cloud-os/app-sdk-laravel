<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Host-owned real-world person or organization identity. */
interface Party
{
    public function key(): int|string;

    public function publicId(): string;

    public function displayLabel(): string;

    public function isPerson(): bool;

    public function isOrganization(): bool;

    public function isArchived(): bool;
}
