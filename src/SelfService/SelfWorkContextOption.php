<?php

declare(strict_types=1);

namespace Nexia\SelfService;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Organization\Contracts\LegalEntity;

/** Safe display metadata for one App-owned exact-self work context. */
final readonly class SelfWorkContextOption
{
    public function __construct(
        public ResourceRef $resource,
        public LegalEntity $legalEntity,
        public string $label,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $effectiveFrom = null,
        public ?string $effectiveUntil = null,
        public bool $isPrimary = false,
    ) {
        if (trim($label) === '') {
            throw new InvalidArgumentException('A self work context requires a non-blank display label.');
        }
    }
}
