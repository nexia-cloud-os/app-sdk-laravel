<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

/** Host-owned Legal Entity identity exposed at an App boundary. */
interface LegalEntity
{
    public function key(): int|string;

    public function publicId(): string;

    public function displayLabel(): string;

    public function code(): string;

    public function partyPublicId(): ?string;

    public function isActiveOrganization(): bool;
}
