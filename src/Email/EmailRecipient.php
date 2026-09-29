<?php

declare(strict_types=1);

namespace Nexia\Email;

/** A resolved recipient; identity remains stable when an address changes. */
final readonly class EmailRecipient
{
    public function __construct(
        public string $identity,
        public string $name,
        public ?string $email,
        public ?string $partyPublicId = null,
        public ?string $appKey = null,
    ) {}
}
