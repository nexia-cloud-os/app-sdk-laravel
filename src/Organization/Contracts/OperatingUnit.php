<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

/** Host-owned Operating Unit identity exposed at an App boundary. */
interface OperatingUnit
{
    public function key(): int|string;

    public function publicId(): string;

    public function displayLabel(): string;

    public function parentKey(): int|string|null;

    public function code(): string;

    public function classification(): string;

    public function accentColor(): ?string;
}
