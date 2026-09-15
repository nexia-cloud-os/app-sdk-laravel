<?php

declare(strict_types=1);

namespace Nexia\SelfService;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;

/** Structured Profile destination; the host serializes it into a safe route. */
final readonly class SelfServiceReturnTarget
{
    public function __construct(
        public string $tab,
        public ?string $view = null,
        public ?string $anchor = null,
        public ?ResourceRef $workContext = null,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_-]*\z/D', $tab) !== 1
            || ($view !== null && preg_match('/\A[a-z][a-z0-9_-]*\z/D', $view) !== 1)
            || ($anchor !== null && preg_match('/\A[a-z][a-z0-9_-]*\z/D', $anchor) !== 1)) {
            throw new InvalidArgumentException('A self-service return target must use canonical destination keys.');
        }
    }
}
