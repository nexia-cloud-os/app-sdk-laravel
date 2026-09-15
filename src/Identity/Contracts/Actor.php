<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Authenticated host actor exposed to an App without leaking the Core user model. */
interface Actor
{
    public function key(): int|string;

    public function publicId(): string;

    public function displayLabel(): string;

    public function partyKey(): int|string|null;

    public function isVerified(): bool;
}
